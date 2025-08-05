<?php
declare(strict_types=1);
/**
 * baserCMS :  Base            // JSON をパースしてMCPリクエストを検証
            $mcpRequest = json_decode($requestBody, true);
            if (!$mcpRequest || !isset($mcpRequest['jsonrpc']) || $mcpRequest['jsonrpc'] !== '2.0') {
                throw new BadRequestException('Invalid MCP request format');
            }

            // MCPリクエストの詳細をログに出力
            error_log("MCP Request: " . json_encode($mcpRequest, JSON_PRETTY_PRINT));

            // SSE クライアントとしてMCPサーバーに接続してリクエストを処理te Development Project <https://basercms.net>
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
use Cake\Event\EventInterface;

/**
 * MCPサーバーへのプロキシコントローラー
 * SSEクライアントとしてMCPサーバーと通信し、HTTPリクエストをMCPプロトコルに変換
 */
class McpProxyController extends Controller
{

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
