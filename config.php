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
