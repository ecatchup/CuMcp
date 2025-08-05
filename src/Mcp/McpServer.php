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

namespace CuMcp\Mcp;

use PhpMcp\Server\Server;
use PhpMcp\Server\ServerBuilder;
use PhpMcp\Schema\ServerCapabilities;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use BaserCore\Utility\BcContainerTrait;
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

        $builder = $builder
            ->withServerInfo('baserCMS MCP Server', '1.0.0')
            ->withCapabilities(new ServerCapabilities(
                tools: true,
                resources: true,
                prompts: true
            ));

        $toolClasses = array_merge(
            BcBlogServer::getToolClasses(),
            BcCustomContentServer::getToolClasses()
        );

        foreach ($toolClasses as $toolClass) {
            $this->registerToolsFromServer($toolClass, $builder);
        }

        // サーバー情報ツールを追加
        $builder = $builder->withTool(
            handler: [self::class, 'serverInfo'],
            name: 'serverInfo',
            description: 'サーバーのバージョンや環境情報を返します',
            inputSchema: [
                'type' => 'object',
                'properties' => []
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
            $connection = ConnectionManager::get('default');
            $config = $connection->config();

            return [
                'success' => true,
                'data' => [
                    'php_version' => PHP_VERSION,
                    'basercms_version' => BcUtil::getVersion(),
                    'cakephp_version' => Configure::version(),
                    'server_time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
