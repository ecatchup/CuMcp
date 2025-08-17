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
use BaserCore\Utility\BcUtil;
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
        // .well-known エンドポイントをルートレベルで設定（認証不要の通常コントローラーを指定）
        $routes->scope('/', function (RouteBuilder $builder) {
            $builder->setRouteClass(InflectedRoute::class);

            $builder->connect('/mcp', ['plugin' => 'CuMcp', 'controller' => 'McpProxy', 'action' => 'index'], ['routeClass' => InflectedRoute::class]);

            // OAuth 2.0 保護リソースメタデータエンドポイント (RFC 9728)
            $builder->connect('/.well-known/oauth-protected-resource', ['plugin' => 'CuMcp', 'controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/.well-known/oauth-protected-resource', ['plugin' => 'CuMcp', 'controller' => 'Oauth2', 'action' => 'protectedResourceMetadata'])->setMethods(['GET']);

            // OAuth 2.0 認可サーバーメタデータエンドポイント (RFC 8414)
            $builder->connect('/.well-known/oauth-authorization-server', ['plugin' => 'CuMcp', 'controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/.well-known/oauth-authorization-server', ['plugin' => 'CuMcp', 'controller' => 'Oauth2', 'action' => 'authorizationServerMetadata'])->setMethods(['GET']);
        });

        $routes->plugin('CuMcp', ['path' => '/cu-mcp'], function (RouteBuilder $builder) {
            $builder->setRouteClass(InflectedRoute::class);

            // Oauth2エンドポイント（認証不要）
            // トークン発行エンドポイント
            $builder->connect('/oauth2/token', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/token', ['controller' => 'Oauth2', 'action' => 'token'])->setMethods(['POST']);

            // トークン検証エンドポイント
            $builder->connect('/oauth2/verify', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/verify', ['controller' => 'Oauth2', 'action' => 'verify'])->setMethods(['POST', 'GET']);

            // クライアント情報取得エンドポイント
            $builder->connect('/oauth2/client-info', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/client-info', ['controller' => 'Oauth2', 'action' => 'clientInfo'])->setMethods(['GET']);

            // RFC 7591 動的クライアント登録プロトコル（認証不要）
            $builder->connect('/oauth2/register', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
            $builder->connect('/oauth2/register', ['controller' => 'Oauth2', 'action' => 'register'])->setMethods(['POST']);

            // クライアント設定エンドポイント（RFC 7591）
            $builder->connect('/oauth2/register/{client_id}', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS'])->setPass(['client_id']);
            $builder->connect('/oauth2/register/{client_id}', ['controller' => 'Oauth2', 'action' => 'clientConfiguration'])->setMethods(['GET', 'PUT', 'DELETE'])->setPass(['client_id']);

            // その他のルート
            $builder->fallbacks(\Cake\Routing\Route\DashedRoute::class);
        });

        // Admin prefix routes for Oauth2 endpoints（認証が必要なエンドポイントのみ）
        $routes->prefix('Admin', ['path' => BcUtil::getPrefix()], function (RouteBuilder $builder) {
            $builder->plugin('CuMcp', ['path' => '/cu-mcp'], function (RouteBuilder $routes) {
                $routes->setRouteClass(InflectedRoute::class);

                // Authorization Code Grant 認可エンドポイント（認証必要）
                $routes->connect('/oauth2/authorize', ['controller' => 'Oauth2', 'action' => 'options'])->setMethods(['OPTIONS']);
                $routes->connect('/oauth2/authorize', ['controller' => 'Oauth2', 'action' => 'authorize'])->setMethods(['GET', 'POST']);

                // MCPサーバー管理
                $routes->get('/mcp-server-manager', ['controller' => 'McpServerManager', 'action' => 'index']);
                $routes->get('/mcp-server-manager/configure', ['controller' => 'McpServerManager', 'action' => 'configure']);
                $routes->post('/mcp-server-manager/configure', ['controller' => 'McpServerManager', 'action' => 'configure']);
                $routes->post('/mcp-server-manager/start', ['controller' => 'McpServerManager', 'action' => 'start']);
                $routes->post('/mcp-server-manager/stop', ['controller' => 'McpServerManager', 'action' => 'stop']);
                $routes->post('/mcp-server-manager/restart', ['controller' => 'McpServerManager', 'action' => 'restart']);
            });
        });

        parent::routes($routes);
    }

}
