<?php
declare(strict_types=1);

namespace CuMcp\Mcp;

use BaserCore\Utility\BcUtil;
use CuMcp\Mcp\BaserCore\BaserCoreServer;
use PhpMcp\Server\Server;
use PhpMcp\Server\ServerBuilder;
use PhpMcp\Schema\ServerCapabilities;
use Cake\Core\Configure;
use PhpMcp\Server\Transports\StdioServerTransport;
use PhpMcp\Server\Transports\StreamableHttpServerTransport;
use CuMcp\Mcp\BcBlog\BcBlogServer;
use CuMcp\Mcp\BcCustomContent\BcCustomContentServer;

/**
 * baserCMS MCP Server
 *
 * baserCMSのデータを外部から操作するためのMCPサーバー
 * 各エンティティサーバーを統合して提供
 */
class McpServer
{

    private Server $server;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->buildServer();
    }

    /**
     * サーバーのビルド
     */
    private function buildServer(): void
    {
        $builder = new ServerBuilder();

        // サーバー名の設定
        $serverName = 'baserCMS MCP Server';
        $serverVersion = '1.0.0';

        $builder = $builder
            ->withServerInfo($serverName, $serverVersion)
            ->withCapabilities(new ServerCapabilities(
                tools: true,
                resources: true,
                prompts: true
            ));

        $this->registerToolsFromServer(array_merge(
            // ChatGPTへの対応のため、search と fetch が実装されているが、
            //ChatGPTでうまく動作しないためコメントアウト
//            BaserCoreServer::getToolClasses(),
            BcBlogServer::getToolClasses(),
            BcCustomContentServer::getToolClasses(),
        ), $builder);

        // サーバー情報ツールを追加
        $builder = $builder->withTool(
            handler: [self::class, 'serverInfo'],
            name: 'serverInfo',
            description: 'サーバーのバージョンや環境情報を返します',
            inputSchema: [
				'type' => 'object',
				'properties' => [
                    'id' => ['type' => 'number', 'description' => 'ID'],
                ]
			]
        );

        $this->server = $builder->build();
    }

    /**
     * ツールクラス配列からツールを登録
     *
     * @param array<string> $toolClasses ツールクラス名の配列
     * @param ServerBuilder $builder サーバービルダー
     * @return void
     */
    private function registerToolsFromServer(array $toolClasses, ServerBuilder &$builder): void
    {
        foreach ($toolClasses as $toolClass) {
            $toolInstance = new $toolClass();
            $builder = $toolInstance->addToolsToBuilder($builder);
        }
    }

    /**
     * 標準入力からサーバーを起動
     */
    public function runStdio(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

    /**
     * SSEでサーバーを起動
     *
     * @param string $host ホスト名
     * @param int $port ポート番号
     */
    public function runSse(string $host, int $port): void
    {
        $transport = new StreamableHttpServerTransport(
            host: $host,
            port: $port,
            mcpPath: '',  // 明示的にパスを指定
            enableJsonResponse: true,
            stateless: true
        );
        $this->server->listen($transport);
    }

    /**
     * サーバー情報を取得
     */
    public function serverInfo(array $arguments = []): array
    {
        try {
            $info = [
                'php_version' => PHP_VERSION,
                'basercms_version' => BcUtil::getVersion(),
                'cakephp_version' => Configure::version(),
                'server_time' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get(),
                'mcp_server_version' => '1.0.0',
                'supported_clients' => ['ChatGPT', 'Claude', 'Custom MCP Clients'],
                'available_transports' => ['stdio', 'sse']
            ];

            return [
                'isError' => false,
                'content' => $info
            ];
        } catch (\Exception $e) {
            return [
                'isError' => true,
                'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * 設定を適用
     */
    public function setConfig(array $config): void
    {
        // 将来的な設定対応のためのメソッド
    }
}
