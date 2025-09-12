<?php
declare(strict_types=1);

use Cake\Log\Engine\FileLog;

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
        /**
         * CSRFチェックをスキップするURL
         */
        'skipCsrfUrl' => [
            'Mcp' => '/cu-mcp',
            // RFC 7591 動的クライアント登録プロトコル（ワイルドカードパターン使用）
            'OAuth2All' => '/cu-mcp/oauth2/*',
            'OAuth2AdminAll' => '/baser/admin/cu-mcp/oauth2/*'
        ]
    ],
    'BcPermission' => [
        /**
         * デフォルトで許可するURL
         */
        'defaultAllows' => [
            'Authorize' => '/cu-mcp/oauth2/authorize'
        ]
    ],
    'Log' => [
        'mcp' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'mcp',
            'scopes' => ['mcp'],
            'levels' => ['info', 'error']
        ]
    ],
];
