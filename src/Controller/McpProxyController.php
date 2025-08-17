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
use CuMcp\OAuth2\Service\OAuth2Service;

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

        // CORS設定（実態に合わせて POST と OPTIONS のみ許可）
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * リクエスト処理前の認証チェック
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $method = $this->request->getMethod();

        // OPTIONS は認証不要
        if ($method === 'OPTIONS') {
            return;
        }

        // 非許可メソッド（POST以外）は 405 を返すだけにしたいため認証をスキップ
        if ($method !== 'POST') {
            return;
        }

        if(in_array($this->request->getData('method'), [
            'initialize',
            'notifications/initialized',
            'tools/list',
            'resources/list',
            'prompts/list'
        ])) {
            // MCPサーバーの初期化メソッドは認証不要
            return;
        } elseif($this->request->getData('method') === 'tools/call') {
            $toolName = $this->request->getData('params.name');
            if(in_array($toolName, ['search', 'fetch'])) {
                return;
            }
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
            $this->throwUnauthorizedException('Missing or invalid authorization header');
        }

        $token = substr($authHeader, 7);
        $tokenData = $this->oauth2Service->validateAccessToken($token);

        if (!$tokenData) {
            $this->throwUnauthorizedException('Invalid or expired access token');
        }

        // トークン情報をリクエストに保存
        $this->request = $this->request
            ->withAttribute('oauth_client_id', $tokenData['client_id'])
            ->withAttribute('oauth_user_id', $tokenData['user_id'])
            ->withAttribute('oauth_scopes', $tokenData['scopes']);
    }

    private function throwUnauthorizedException(string $message): void
    {
        $siteUrl = env('SITE_URL', 'https://localhost');
        $baseUrl = rtrim($siteUrl, '/');
        $resourceMetadataUrl = $baseUrl . '/.well-known/oauth-protected-resource';

        $e = new UnauthorizedException($message);
        $e->setHeader('WWW-Authenticate', 'Bearer resource_metadata="' . $resourceMetadataUrl . '"');
        throw $e;
    }

    /**
     * MCPサーバーへのプロキシ処理
     * /mcp へのアクセスを内部MCPサーバーに転送
     * OPTIONSリクエストも含めて全てここで処理
     */
    public function index()
    {
        // OPTIONSリクエストの場合はCORSレスポンスを返す
        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->_handleOptionsRequest();
        }

        // POST以外のメソッドは許可しない
        if ($this->request->getMethod() !== 'POST') {
            $this->response = $this->response
                ->withHeader('Allow', 'POST, OPTIONS')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
                ->withStatus(405);
            return $this->response;
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

            // CakePHPのリクエストオブジェクトからJSONボディを取得
            $requestBody = (string)$this->request->getBody();

            if (empty($requestBody)) {
                // 空ボディは不正
                $this->response = $this->response
                    ->withHeader('Allow', 'POST, OPTIONS')
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
                    ->withStatus(400);
                return $this->response;
            }

            // JSONをパースしてMCPリクエストを検証
            $mcpRequest = json_decode($requestBody, true);
            if (!$mcpRequest || !isset($mcpRequest['jsonrpc']) || $mcpRequest['jsonrpc'] !== '2.0') {
                throw new BadRequestException('Invalid MCP request format');
            }

            // SSEクライアントとしてMCPサーバーに接続してリクエストを処理
            $response = $this->sendMcpRequest($config, $mcpRequest);

            $this->response = $this->response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With, Origin')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withStringBody(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

            // MCP Inspector対応：プロトコルバージョンとcapabilitiesを調整
            if (isset($responseData['result']) && isset($mcpRequest['method']) && $mcpRequest['method'] === 'initialize') {
                // capabilitiesにツールの存在を示す（実際のツールリストはtools/listで取得）
                $responseData['result']['capabilities'] = [
                    'tools' => ['listChanged' => true],  // 空オブジェクトでツール機能があることを示す
                    'resources' => ['listChanged' => true],
                    'prompts' => ['listChanged' => true]
                ];
//                $responseData['result']['protocolVersion'] = '2025-06-18';
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
            ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
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
    }

    /**
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
            return array_merge($defaultConfig, $savedConfig?: []);
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
