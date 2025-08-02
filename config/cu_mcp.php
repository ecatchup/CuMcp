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

/**
 * CuMcp Plugin Configuration
 */
return [
    'CuMcp' => [
        // MCPサーバーの設定
        'server' => [
            'name' => 'baserCMS MCP Server',
            'version' => '1.0.0',
            'description' => 'baserCMSデータを操作するためのMCPサーバー',
            'author' => 'baserCMS',
            'license' => 'MIT'
        ],

        // サポートするテーブル
        'supported_tables' => [
            'blog_posts' => 'BcBlog.BlogPosts',
            'blog_categories' => 'BcBlog.BlogCategories',
            'blog_contents' => 'BcBlog.BlogContents',
            'blog_tags' => 'BcBlog.BlogTags',
            'custom_contents' => 'BcCustomContent.CustomContents',
            'custom_entries' => 'BcCustomContent.CustomEntries',
            'custom_tables' => 'BcCustomContent.CustomTables',
            'custom_fields' => 'BcCustomContent.CustomFields',
            'custom_links' => 'BcCustomContent.CustomLinks'
        ],

        // デフォルト設定
        'defaults' => [
            'blog_content_id' => 1,
            'user_id' => 1,
            'custom_table_id' => 1,
            'limit' => 20,
            'status' => [
                'published' => 1,
                'draft' => 0
            ]
        ],

        // セキュリティ設定
        'security' => [
            'allowed_hosts' => ['localhost', '127.0.0.1'],
            'require_auth' => false,
            'max_request_size' => 1024 * 1024, // 1MB
            'rate_limit' => [
                'enabled' => false,
                'requests_per_minute' => 60
            ]
        ],

        // ログ設定
        'logging' => [
            'enabled' => true,
            'level' => 'info', // debug, info, warning, error
            'file' => TMP . 'logs' . DS . 'mcp_server.log',
            'rotate' => true,
            'max_size' => 10 * 1024 * 1024 // 10MB
        ]
    ]
];
