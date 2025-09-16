<?php
declare(strict_types=1);
$message = [];
if(!is_writable(CONFIG)) $message[] = CONFIG . ' に書き込み権限がありません。インストールする前に書き込み権限を与えてください。';
if(!is_writable(CONFIG . '.env')) $message[] = CONFIG . '.env に書き込み権限がありません。インストールする前に書き込み権限を与えてください。';
$message[] = 'インストール時には、認証必要領域の Web API（baser Admin Api）を有効を有効化します。';

return [
    'type' => 'Plugin',
    'title' => 'baserCMS MCP Server',
    'description' => 'baserCMSをAIエージェントから操作するためのMCPサーバーを提供します。',
    'author' => 'Catchup, Inc.',
    'url' => 'https://catchup.co.jp',
    'installMessage' =>implode("<br>", $message),
    'adminLink' => [
        'plugin' => 'CuMcp',
        'controller' => 'McpServerManager',
        'action' => 'index'
    ],
];
