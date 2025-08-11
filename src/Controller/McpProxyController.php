<?php
declare(strict_types=1);
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.7
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace CuMcp\Controller;

use Cake\Http\Client;
use Cake\Controller\Controller;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ServiceUnavailableException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Event\EventInterface;
use CuMcp\Service\OAuth2Service;

/**
 * MCPサーバーへのプロキシコントローラー
 * SSEクライアントとしてMCPサーバーと通信し、HTTPリクエストをMCPプロトコルに変換
 * OAuth2認証対応
 */
class McpProxyController extends Controller
{
    /**
     * OAuth2サービス
     *
     * @var OAuth2Service
     */
    private OAuth2Service $oauth2Service;

    /**
     * 初期化
     */
    public function initialize(): void
    {
        parent::initialize();

        // OAuth2サービスを初期化
        $this->oauth2Service = new OAuth2Service();

        // MCPリクエスト用にセキュリティ関連のコンポーネントを無効化
        if ($this->components()->has('Security')) {
            $this->Security->setConfig('validatePost', false);
            $this->Security->setConfig('csrfCheck', false);
        }

        if ($this->components()->has('FormProtection')) {
            $this->FormProtection->setConfig('validate', false);
        }

        // CakePHP5の場合のCSRF対策
        if ($this->components()->has('Csrf')) {
            $this->removeComponent('Csrf');
        }

        // CORS設定
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * リクエスト処理前の認証チェック
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // OPTIONSリクエストは認証不要
        if ($this->request->getMethod() === 'OPTIONS') {
            return;
        }

        // OAuth2トークンの検証
        $this->validateOAuth2Token();
    }

    /**
     * OAuth2トークンの検証
     *
     * @throws UnauthorizedException
     */
    private function validateOAuth2Token(): void
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new UnauthorizedException('Missing or invalid authorization header');
        }

        $token = substr($authHeader, 7);
        $tokenData = $this->oauth2Service->validateAccessToken($token);

        if (!$tokenData) {
            throw new UnauthorizedException('Invalid or expired access token');
        }

        // トークン情報をリクエストに保存
        $this->request = $this->request
            ->withAttribute('oauth_client_id', $tokenData['client_id'])
            ->withAttribute('oauth_user_id', $tokenData['user_id'])
            ->withAttribute('oauth_scopes', $tokenData['scopes']);
    }

    /**
     * MCPサーバーへのプロキシ処理
     * /cu-mcp/mcp-proxy.json へのアクセスを内部MCPサーバーに転送
     * OPTIONSリクエストも含めて全てここで処理
     */
    public function index()
    {
        // OPTIONSリクエストの場合はCORSレスポンスを返す
        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->_handleOptionsRequest();
        }

        try {
            // MCPサーバーの設定を取得
            $config = $this->getMcpServerConfig();

            // MCPサーバーが起動しているかチェック
            if (!$this->isMcpServerRunning($config)) {
                throw new ServiceUnavailableException(
                    'MCPサーバーが起動していません。管理画面からMCPサーバーを起動してください。'
                );
            }

            // JSONボディを直接取得してMCPリクエストとしてパース
            $requestBody = file_get_contents('php://input');

            if(empty($requestBody)) {
                return $this->response;
            }

            // JSONをパースしてMCPリクエストを検証
            $mcpRequest = json_decode($requestBody, true);
            if (!$mcpRequest || !isset($mcpRequest['jsonrpc']) || $mcpRequest['jsonrpc'] !== '2.0') {
                throw new BadRequestException('Invalid MCP request format');
            }

            // MCPリクエストの詳細をログに出力
            error_log("MCP Request: " . json_encode($mcpRequest, JSON_PRETTY_PRINT));

            // SSEクライアントとしてMCPサーバーに接続してリクエストを処理
            $response = $this->sendMcpRequest($config, $mcpRequest);

            // MCP応答をHTTPレスポンスとして返す
            $this->response = $this->response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withStringBody(json_encode($response));

        } catch (ServiceUnavailableException $e) {
            throw $e;
        } catch (BadRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServiceUnavailableException(
                'MCPサーバーとの通信に失敗しました: ' . $e->getMessage()
            );
        }

        return $this->response;
    }

    /**
     * StreamableHttpServerTransport用のMCPリクエスト送信
     * 直接JSONエンドポイントとして通信（SSE初期化不要）
     */
    private function sendMcpRequest(array $config, array $mcpRequest): array
    {
        // StreamableHttpServerTransportの場合はルートパス（/）を使用
        $jsonUrl = "http://127.0.0.1:{$config['port']}/";

        try {
            $client = new Client(['timeout' => 10]);
            $response = $client->post($jsonUrl, json_encode($mcpRequest), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                return [
                	"jsonrpc" => "2.0",
  					"result" => []
				];
            }

            return $responseData;

        } catch (\Exception $e) {
            throw new \Exception('MCPサーバーとの通信に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * OPTIONSリクエストの処理（CORS プリフライト対応）
     */
    private function _handleOptionsRequest()
    {
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withStatus(200);

        return $this->response;
    }

    /**
     * OPTIONSリクエストの処理（CORS プリフライト対応）
     * 後方互換性のため残しているが、実際は_handleOptionsRequestが使用される
     */
    public function options()
    {
        return $this->_handleOptionsRequest();
    }    /**
     * MCPサーバーの設定を取得
     */
    private function getMcpServerConfig(): array
    {
        $configFile = CONFIG . 'cu_mcp_server.json';

        $defaultConfig = [
            'host' => '127.0.0.1',
            'port' => '3000'
        ];

        if (file_exists($configFile)) {
            $savedConfig = json_decode(file_get_contents($configFile), true);
            return array_merge($defaultConfig, $savedConfig ?: []);
        }

        return $defaultConfig;
    }

    /**
     * MCPサーバーが起動しているかチェック
     */
    private function isMcpServerRunning(array $config): bool
    {
        try {
            $client = new Client(['timeout' => 3]);
            // POSTリクエストでサーバーの生存確認（軽量なリクエスト）
            $response = $client->post("http://127.0.0.1:{$config['port']}/", json_encode([
                'jsonrpc' => '2.0',
                'id' => 'ping',
                'method' => 'tools/list'  // 実際に存在するメソッドを使用
            ]), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            // レスポンスが返ってきたらサーバーが起動していると判定
            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            return false;
        }
    }

}
