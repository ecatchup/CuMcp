<?php
declare(strict_types=1);

return [
    'type' => 'Plugin',
    'title' => 'baserCMS MCP Server',
    'description' => 'baserCMSをAIエージェントから操作するためのMCPサーバーを提供します。',
    'author' => 'Catchup, Inc.',
    'url' => 'https://catchup.co.jp',
    'installMessage' => '',
    'adminLink' => [
        'plugin' => 'CuMcp',
        'controller' => 'McpServerManager',
        'action' => 'index'
    ],
];
