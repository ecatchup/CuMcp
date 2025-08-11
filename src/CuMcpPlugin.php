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

namespace CuMcp;

use BaserCore\BcPlugin;
use Cake\Console\CommandCollection;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Route\InflectedRoute;

/**
 * Plugin for CuMcp
 */
class CuMcpPlugin extends BcPlugin
{

    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        // MCPサーバーコマンドを追加
        $commands->add('cu_mcp.server', \CuMcp\Command\McpServerCommand::class);

        $commands = parent::console($commands);

        return $commands;
    }

    /**
     * Add routes for the plugin.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin('CuMcp', ['path' => '/cu-mcp'], function (RouteBuilder $builder) {
            $builder->setRouteClass(InflectedRoute::class);

            // OAuth2認証エンドポイント
            // HTTPメソッド別のルーティング設定（CORS対応のためOPTIONSも追加）
            $builder->connect('/oauth2/token', ['controller' => 'OAuth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/token', ['controller' => 'OAuth2', 'action' => 'token'])->setMethods(['POST']);

            $builder->connect('/oauth2/verify', ['controller' => 'OAuth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/verify', ['controller' => 'OAuth2', 'action' => 'verify'])->setMethods(['POST', 'GET']);

            $builder->connect('/oauth2/client-info', ['controller' => 'OAuth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/client-info', ['controller' => 'OAuth2', 'action' => 'clientInfo'])->setMethods(['GET']);

            // MCPプロキシ（最重要: 外部からのMCPアクセス）
            // JSON API用（.json拡張子対応）
            $builder->connect('/mcp-proxy.json', ['controller' => 'McpProxy', 'action' => 'index', '_ext' => 'json'], ['routeClass' => InflectedRoute::class]);

            // 通常のプロキシルート（バックアップ）
            $builder->connect('/mcp-proxy/**', ['controller' => 'McpProxy', 'action' => 'index']);
            $builder->connect('/mcp-proxy', ['controller' => 'McpProxy', 'action' => 'index']);

            // 管理画面
            $builder->prefix('Admin', function (RouteBuilder $builder) {
                $builder->setRouteClass(InflectedRoute::class);
                // MCPサーバー管理
                $builder->get('/mcp-server-manager', ['controller' => 'McpServerManager', 'action' => 'index']);
                $builder->get('/mcp-server-manager/configure', ['controller' => 'McpServerManager', 'action' => 'configure']);
                $builder->post('/mcp-server-manager/configure', ['controller' => 'McpServerManager', 'action' => 'configure']);
                $builder->post('/mcp-server-manager/start', ['controller' => 'McpServerManager', 'action' => 'start']);
                $builder->post('/mcp-server-manager/stop', ['controller' => 'McpServerManager', 'action' => 'stop']);
                $builder->post('/mcp-server-manager/restart', ['controller' => 'McpServerManager', 'action' => 'restart']);
            });

            // その他のルート
            $builder->fallbacks(\Cake\Routing\Route\DashedRoute::class);
        });

        parent::routes($routes);
    }

}
