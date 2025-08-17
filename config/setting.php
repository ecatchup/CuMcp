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

return [
    'BcApp' => [
        /**
          * System Navigation
          */
        'adminNavigation' => [
            'Systems' => [
                'CuMcpServerManager' => [
                    'title' => 'MCPサーバー管理',
                    'type' => 'system',
                    'url' => [
                        'prefix' => 'Admin',
                        'plugin' => 'CuMcp',
                        'controller' => 'McpServerManager',
                        'action' => 'index'
                    ],
                    'currentRegex' => '/\/cu-mcp\/admin\/mcp-server-manager.*/',
                    'icon' => 'bca-icon--custom'
                ],
            ]
        ],
        'skipCsrfUrl' => [
            'Mcp' => '/mcp',
            // RFC 7591 動的クライアント登録プロトコル（ワイルドカードパターン使用）
            'OAuth2All' => '/cu-mcp/oauth2/*',
            'OAuth2AdminAll' => '/baser/admin/cu-mcp/oauth2/*'
        ]
    ],
    'BcPermission' => [
        'defaultAllows' => [
            'Authorize' => '/baser/admin/cu-mcp/oauth2/authorize'
        ]
    ]
];
