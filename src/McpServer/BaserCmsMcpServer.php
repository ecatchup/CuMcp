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

namespace CuMcp\McpServer;

use PhpMcp\Schema\Tool;
use Cake\Core\Configure;
use PhpMcp\Schema\Prompt;
use PhpMcp\Server\Server;
use Cake\ORM\TableRegistry;
use PhpMcp\Schema\Resource;
use PhpMcp\Server\ServerBuilder;
use PhpMcp\Schema\Implementation;
use PhpMcp\Schema\PromptArgument;
use PhpMcp\Schema\ServerCapabilities;
use Cake\Datasource\ConnectionManager;
use PhpMcp\Server\Transports\HttpServerTransport;
use PhpMcp\Server\Transports\StdioServerTransport;
use PhpMcp\Server\Transports\StreamableHttpServerTransport;

/**
 * baserCMS MCP Server
 *
 * baserCMSのデータを外部から操作するためのMCPサーバー
 */
class BaserCmsMcpServer
{
    private Server $server;
    private array $supportedTables;

    public function __construct()
    {
        // サポートするテーブルの設定
        $this->supportedTables = [
            'blog_posts' => 'BcBlog.BlogPosts',
            'blog_categories' => 'BcBlog.BlogCategories',
            'blog_contents' => 'BcBlog.BlogContents',
            'blog_tags' => 'BcBlog.BlogTags',
            'custom_contents' => 'BcCustomContent.CustomContents',
            'custom_entries' => 'BcCustomContent.CustomEntries',
            'custom_tables' => 'BcCustomContent.CustomTables',
            'custom_fields' => 'BcCustomContent.CustomFields',
            'custom_links' => 'BcCustomContent.CustomLinks'
        ];

        $this->buildServer();
    }

    private function buildServer(): void
    {
        $builder = new ServerBuilder();

        $this->server = $builder
            ->withServerInfo('baserCMS MCP Server', '1.0.0')
            ->withCapabilities(new ServerCapabilities(
                tools: true,
                resources: true,
                prompts: true
            ))
            ->withTool(
                handler: [self::class, 'addBlogPost'],
                name: 'addBlogPost',
                description: 'ブログ記事を追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => '記事タイトル（必須）'],
                        'detail' => ['type' => 'string', 'description' => '記事詳細（必須）'],
                        'category' => ['type' => 'string', 'description' => 'カテゴリ名（省略時はカテゴリなし）'],
                        'blog_content' => ['type' => 'string', 'description' => 'ブログコンテンツ名（省略時はデフォルト）'],
                        'email' => ['type' => 'string', 'format' => 'email', 'description' => 'ユーザーのメールアドレス（省略時はデフォルトユーザー）']
                    ],
                    'required' => ['title', 'detail']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogPosts'],
                name: 'getBlogPosts',
                description: 'ブログ記事の一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は10件）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogPost'],
                name: 'getBlogPost',
                description: '指定されたIDのブログ記事を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => '記事ID（必須）'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editBlogPost'],
                name: 'editBlogPost',
                description: 'ブログ記事を編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => '記事ID（必須）'],
                        'title' => ['type' => 'string', 'description' => '記事タイトル'],
                        'detail' => ['type' => 'string', 'description' => '記事詳細'],
                        'content' => ['type' => 'string', 'description' => '記事概要'],
                        'category' => ['type' => 'string', 'description' => 'カテゴリ名'],
                        'blog_category_id' => ['type' => 'number', 'description' => 'カテゴリID（categoryと併用不可）'],
                        'blog_content' => ['type' => 'string', 'description' => 'ブログコンテンツ名'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'name' => ['type' => 'string', 'description' => '記事のスラッグ'],
                        'eye_catch' => ['type' => 'string', 'description' => 'アイキャッチ画像（URL）'],
                        'user_id' => ['type' => 'number', 'description' => 'ユーザーID（emailと併用不可）'],
                        'email' => ['type' => 'string', 'format' => 'email', 'description' => 'ユーザーのメールアドレス']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogPost'],
                name: 'deleteBlogPost',
                description: '指定されたIDのブログ記事を削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => '記事ID（必須）'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'addCustomEntry'],
                name: 'addCustomEntry',
                description: 'カスタムエントリーを追加します。カスタムエントリーを追加するには、カスタムテーブルが必要です。事前に作成するか既存のカスタムテーブルIDを指定してください。フロントエンドに表示させるには、カスタムテーブルがカスタムコンテンツと紐づいている必要があります。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'タイトル（必須）'],
                        'name' => ['type' => 'string', 'default' => '', 'description' => 'スラッグ（初期値空文字）'],
                        'status' => ['type' => 'boolean', 'default' => false, 'description' => '公開状態（デフォルト：false）'],
                        'published' => ['type' => 'string', 'description' => '公開日（YYYY-MM-DD HH:mm:ss形式、省略時は当日）'],
                        'publish_begin' => ['type' => 'string', 'description' => '公開開始日（YYYY-MM-DD HH:mm:ss形式、省略可）'],
                        'publish_end' => ['type' => 'string', 'description' => '公開終了日（YYYY-MM-DD HH:mm:ss形式、省略可）'],
                        'creator_id' => ['type' => 'number', 'default' => 1, 'description' => '投稿者ID（デフォルト初期ユーザー）'],
                        'custom_fields' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'カスタムフィールドの値（フィールド名をキーとするオブジェクト）、ファイルアップロードのフィールドの場合は、参照が可能なファイルのパスを指定します'
                        ]
                    ],
                    'required' => ['custom_table_id', 'title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomEntries'],
                name: 'getCustomEntries',
                description: 'カスタムエントリーの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'limit' => ['type' => 'number', 'default' => 20, 'description' => '取得件数（デフォルト: 20）'],
                        'page' => ['type' => 'number', 'default' => 1, 'description' => 'ページ番号（デフォルト: 1）'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 非公開, 1: 公開）']
                    ],
                    'required' => ['custom_table_id']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomEntry'],
                name: 'getCustomEntry',
                description: '指定されたIDのカスタムエントリーを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）']
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomEntry'],
                name: 'editCustomEntry',
                description: '指定されたIDのカスタムエントリーを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'タイトル'],
                        'name' => ['type' => 'string', 'description' => 'スラッグ'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'publish_begin' => ['type' => 'string', 'description' => '公開開始日（YYYY-MM-DD HH:mm:ss形式）'],
                        'publish_end' => ['type' => 'string', 'description' => '公開終了日（YYYY-MM-DD HH:mm:ss形式）'],
                        'published' => ['type' => 'string', 'description' => '公開日（YYYY-MM-DD HH:mm:ss形式）'],
                        'creator_id' => ['type' => 'number', 'description' => '投稿者ID'],
                        'custom_fields' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'カスタムフィールドの値（フィールド名をキーとするオブジェクト）'
                        ]
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomEntry'],
                name: 'deleteCustomEntry',
                description: '指定されたIDのカスタムエントリーを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）']
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            )
            ->withTool(
                handler: [self::class, 'serverInfo'],
                name: 'serverInfo',
                description: 'サーバーのバージョンや環境情報を返します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => []
                ]
            )
            ->build();
//            ->withTool(
//                handler: [self::class, 'getServerInfo'],
//                name: 'serverInfo',
//                description: 'サーバーのバージョンや環境情報を返します',
//                inputSchema: [
//                    'type' => 'object',
//                    'properties' => (Object)[]
//				]
//            )
//            ->build();
    }

    public function runStdio(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

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

    public function addBlogPost(array $arguments): array
    {
        try {
            $blogPostsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogPosts');

            $data = [
                'title' => $arguments['title'],
                'detail' => $arguments['detail'],
                'blog_content_id' => $this->getBlogContentId($arguments['blog_content'] ?? null),
                'user_id' => $this->getUserId($arguments['email'] ?? null),
                'status' => 1, // 公開
                'posted' => date('Y-m-d H:i:s')
            ];

            // カテゴリ設定
            if (!empty($arguments['category'])) {
                $data['blog_category_id'] = $this->getBlogCategoryId($arguments['category'], $data['blog_content_id']);
            }

            $entity = $blogPostsTable->newEntity($data);
            $result = $blogPostsTable->save($entity);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログ記事の保存に失敗しました',
                    'errors' => $entity->getErrors()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function getBlogPosts(array $arguments): array
    {
        try {
            $blogPostsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogPosts');

            $query = $blogPostsTable->find();

            if (!empty($arguments['blog_content_id'])) {
                $query->where(['blog_content_id' => $arguments['blog_content_id']]);
            }

            if (!empty($arguments['keyword'])) {
                $query->where([
                    'OR' => [
                        'title LIKE' => '%' . $arguments['keyword'] . '%',
                        'detail LIKE' => '%' . $arguments['keyword'] . '%'
                    ]
                ]);
            }

            if (isset($arguments['status'])) {
                $query->where(['status' => $arguments['status']]);
            }

            $limit = $arguments['limit'] ?? 10;
            $page = $arguments['page'] ?? 1;

            $query->limit($limit)->page($page);
            $results = $query->toArray();

            return [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'count' => count($results)
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

    public function getBlogPost(array $arguments): array
    {
        try {
            $blogPostsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogPosts');

            $query = $blogPostsTable->find()->where(['id' => $arguments['id']]);

            // ブログコンテンツIDが指定されている場合は条件に追加
            if (!empty($arguments['blog_content_id'])) {
                $query->where(['blog_content_id' => $arguments['blog_content_id']]);
            }

            $result = $query->first();

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログ記事が見つかりません'
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function editBlogPost(array $arguments): array
    {
        try {
            $blogPostsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogPosts');

            $entity = $blogPostsTable->get($arguments['id']);

            $data = array_intersect_key($arguments, array_flip([
                'title', 'detail', 'content', 'status', 'name', 'eye_catch'
            ]));

            if (!empty($arguments['category'])) {
                $data['blog_category_id'] = $this->getBlogCategoryId(
                    $arguments['category'],
                    $arguments['blog_content_id'] ?? $entity->blog_content_id
                );
            }

            if (!empty($arguments['email'])) {
                $data['user_id'] = $this->getUserId($arguments['email']);
            }

            $entity = $blogPostsTable->patchEntity($entity, $data);
            $result = $blogPostsTable->save($entity);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログ記事の更新に失敗しました',
                    'errors' => $entity->getErrors()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function deleteBlogPost(array $arguments): array
    {
        try {
            $blogPostsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogPosts');

            $entity = $blogPostsTable->get($arguments['id']);
            $result = $blogPostsTable->delete($entity);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ブログ記事を削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログ記事の削除に失敗しました'
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function addCustomEntry(array $arguments): array
    {
        try {
            $customEntriesTable = TableRegistry::getTableLocator()->get('BcCustomContent.CustomEntries');

            $data = [
                'custom_table_id' => $arguments['custom_table_id'],
                'title' => $arguments['title'],
                'name' => $arguments['name'] ?? '',
                'status' => $arguments['status'] ?? false,
                'published' => $arguments['published'] ?? date('Y-m-d H:i:s'),
                'creator_id' => $arguments['creator_id'] ?? 1
            ];

            if (!empty($arguments['publish_begin'])) {
                $data['publish_begin'] = $arguments['publish_begin'];
            }

            if (!empty($arguments['publish_end'])) {
                $data['publish_end'] = $arguments['publish_end'];
            }

            $entity = $customEntriesTable->newEntity($data);
            $result = $customEntriesTable->save($entity);

            if ($result) {
                // カスタムフィールドの値を保存
                if (!empty($arguments['custom_fields'])) {
                    $this->saveCustomFieldValues($result->id, $arguments['custom_fields']);
                }

                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの保存に失敗しました',
                    'errors' => $entity->getErrors()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function getCustomEntries(array $arguments): array
    {
        try {
            $customEntriesTable = TableRegistry::getTableLocator()->get('BcCustomContent.CustomEntries');

            $query = $customEntriesTable->find()
                ->where(['custom_table_id' => $arguments['custom_table_id']]);

            if (isset($arguments['status'])) {
                $query->where(['status' => $arguments['status']]);
            }

            $limit = $arguments['limit'] ?? 20;
            $page = $arguments['page'] ?? 1;

            $query->limit($limit)->page($page);
            $results = $query->toArray();

            return [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'count' => count($results)
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

    public function getCustomEntry(array $arguments): array
    {
        try {
            $customEntriesTable = TableRegistry::getTableLocator()->get('BcCustomContent.CustomEntries');

            $query = $customEntriesTable->find()
                ->where([
                    'custom_table_id' => $arguments['custom_table_id'],
                    'id' => $arguments['id']
                ]);

            $result = $query->first();

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムエントリーが見つかりません'
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function editCustomEntry(array $arguments): array
    {
        try {
            $customEntriesTable = TableRegistry::getTableLocator()->get('BcCustomContent.CustomEntries');

            $entity = $customEntriesTable->find()
                ->where([
                    'custom_table_id' => $arguments['custom_table_id'],
                    'id' => $arguments['id']
                ])
                ->first();

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムエントリーが見つかりません'
                ];
            }

            $data = array_intersect_key($arguments, array_flip([
                'title', 'name', 'status', 'publish_begin', 'publish_end', 'published', 'creator_id'
            ]));

            // カスタムフィールドの値を追加
            if (!empty($arguments['custom_fields'])) {
                $data = array_merge($data, $arguments['custom_fields']);
            }

            $entity = $customEntriesTable->patchEntity($entity, $data);
            $result = $customEntriesTable->save($entity);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの更新に失敗しました',
                    'errors' => $entity->getErrors()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function deleteCustomEntry(array $arguments): array
    {
        try {
            $customEntriesTable = TableRegistry::getTableLocator()->get('BcCustomContent.CustomEntries');

            $entity = $customEntriesTable->find()
                ->where([
                    'custom_table_id' => $arguments['custom_table_id'],
                    'id' => $arguments['id']
                ])
                ->first();

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムエントリーが見つかりません'
                ];
            }

            $result = $customEntriesTable->delete($entity);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムエントリーを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの削除に失敗しました'
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function serverInfo(array $arguments = []): array
    {
        try {
            return [
                'success' => true,
                'data' => [
                    'basercms_version' => Configure::read('BcApp.version'),
                    'php_version' => PHP_VERSION,
                    'cakephp_version' => Configure::version(),
                    'server_time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                    'database' => $this->getDatabaseInfo()
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

    public function getDatabaseResource(string $uri): array
    {
        try {
            $connection = ConnectionManager::get('default');
            $config = $connection->config();

            return [
                'success' => true,
                'data' => [
                    'uri' => $uri,
                    'driver' => $config['driver'] ?? 'unknown',
                    'host' => $config['host'] ?? 'unknown',
                    'database' => $config['database'] ?? 'unknown',
                    'tables' => array_keys($this->supportedTables)
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

    public function analyzeBaserCmsData(array $arguments): array
    {
        try {
            $tableName = $arguments['table_name'];
            $analysisType = $arguments['analysis_type'] ?? 'summary';

            if (!isset($this->supportedTables[$tableName])) {
                return [
                    'error' => true,
                    'message' => "サポートされていないテーブル: {$tableName}"
                ];
            }

            $tableClass = $this->supportedTables[$tableName];
            $table = TableRegistry::getTableLocator()->get($tableClass);

            switch ($analysisType) {
                case 'summary':
                    $count = $table->find()->count();
                    $latest = $table->find()->orderAsc('created')->first();

                    return [
                        'success' => true,
                        'data' => [
                            'table' => $tableName,
                            'analysis_type' => $analysisType,
                            'total_records' => $count,
                            'latest_record' => $latest ? $latest->toArray() : null
                        ]
                    ];

                case 'trends':
                    // トレンド分析の実装
                    return [
                        'success' => true,
                        'data' => [
                            'table' => $tableName,
                            'analysis_type' => $analysisType,
                            'message' => 'トレンド分析は今後実装予定です'
                        ]
                    ];

                case 'performance':
                    // パフォーマンス分析の実装
                    return [
                        'success' => true,
                        'data' => [
                            'table' => $tableName,
                            'analysis_type' => $analysisType,
                            'message' => 'パフォーマンス分析は今後実装予定です'
                        ]
                    ];

                default:
                    return [
                        'error' => true,
                        'message' => "サポートされていない分析タイプ: {$analysisType}"
                    ];
            }
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    private function getBlogContentId(?string $blogContentName): int
    {
        if (empty($blogContentName)) {
            return 1; // デフォルト
        }

        $blogContentsTable = TableRegistry::getTableLocator()->get('BcBlog.BlogContents');
        $content = $blogContentsTable->find()
            ->where(['name' => $blogContentName])
            ->first();

        return $content ? $content->id : 1;
    }

    private function getBlogCategoryId(string $categoryName, int $blogContentId): ?int
    {
        $blogCategoriesTable = TableRegistry::getTableLocator()->get('BcBlog.BlogCategories');
        $category = $blogCategoriesTable->find()
            ->where([
                'name' => $categoryName,
                'blog_content_id' => $blogContentId
            ])
            ->first();

        return $category ? $category->id : null;
    }

    private function getUserId(?string $email): int
    {
        if (empty($email)) {
            return 1; // デフォルトユーザー
        }

        $usersTable = TableRegistry::getTableLocator()->get('BaserCore.Users');
        $user = $usersTable->find()
            ->where(['email' => $email])
            ->first();

        return $user ? $user->id : 1;
    }

    private function saveCustomFieldValues(int $entryId, array $customFields): void
    {
        // カスタムフィールドの値保存ロジック
        // 実際の実装では、BcCustomContent プラグインのサービスを使用
    }

    private function getDatabaseInfo(): array
    {
        $connection = ConnectionManager::get('default');
        $config = $connection->config();

        return [
            'driver' => $config['driver'] ?? 'unknown',
            'host' => $config['host'] ?? 'unknown',
            'database' => $config['database'] ?? 'unknown'
        ];
    }
}
