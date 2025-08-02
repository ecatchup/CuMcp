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

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/**
 * CuMcp プラグインのルーティング設定
 */
$routes->plugin('CuMcp', ['path' => '/cu-mcp'], function (RouteBuilder $builder) {
    $builder->setRouteClass(InflectedRoute::class);
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

    // 管理者用API（従来のAPI、必要に応じて）
    $builder->prefix('Api/Admin', function (RouteBuilder $builder) {
        $builder->setRouteClass(InflectedRoute::class);

        // MCPサーバー情報
        $builder->get('/mcp/server-info', ['controller' => 'Mcp', 'action' => 'serverInfo']);

        // ツール情報
        $builder->get('/mcp/tool-info', ['controller' => 'Mcp', 'action' => 'toolInfo']);

        // ブログ記事追加（通常のHTTP API）
        $builder->post('/mcp/add-blog-post', ['controller' => 'Mcp', 'action' => 'addBlogPost']);

        // SSEエンドポイント
        $builder->get('/mcp/stream-add-blog-post', ['controller' => 'Mcp', 'action' => 'streamAddBlogPost']);
        $builder->post('/mcp/stream-bulk-add-blog-posts', ['controller' => 'Mcp', 'action' => 'streamBulkAddBlogPosts']);
    });
});
