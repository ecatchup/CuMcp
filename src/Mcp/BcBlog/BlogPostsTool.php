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

namespace CuMcp\Mcp\BcBlog;

use BaserCore\Utility\BcContainerTrait;
use BcBlog\Service\BlogPostsServiceInterface;
use BcBlog\Service\BlogContentsServiceInterface;
use BcBlog\Service\BlogCategoriesServiceInterface;
use BaserCore\Service\UsersServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログ記事ツールクラス
 *
 * ブログ記事のCRUD操作を提供
 */
class BlogPostsTool
{
    use BcContainerTrait;
    /**
     * ブログ記事関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
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
            );
    }

    /**
     * ブログ記事を追加
     */
    public function addBlogPost(array $arguments): array
    {
        try {
            $blogPostsService = $this->getService(BlogPostsServiceInterface::class);

            // ユーザーIDを取得
            $userId = 1; // デフォルトユーザー
            if (!empty($arguments['email'])) {
                try {
                    $usersService = $this->getService(UsersServiceInterface::class);
                    $conditions = ['email' => $arguments['email']];
                    $user = $usersService->getIndex($conditions)->first();
                    $userId = $user ? $user->id : 1;
                } catch (\Exception $e) {
                    $userId = 1; // エラー時はデフォルト
                }
            }

            $data = [
                'title' => $arguments['title'],
                'detail' => $arguments['detail'],
                'blog_content_id' => $this->getBlogContentId($arguments['blog_content'] ?? null),
                'user_id' => $userId,
                'status' => 1, // 公開
                'posted' => date('Y-m-d H:i:s')
            ];

            // カテゴリ設定
            if (!empty($arguments['category'])) {
                $data['blog_category_id'] = $this->getBlogCategoryId($arguments['category'], $data['blog_content_id']);
            }

            $result = $blogPostsService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログ記事の保存に失敗しました'
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

    /**
     * ブログ記事一覧を取得
     */
    public function getBlogPosts(array $arguments): array
    {
        try {
            $blogPostsService = $this->getService(BlogPostsServiceInterface::class);

            $conditions = [];
            if (!empty($arguments['blog_content_id'])) {
                $conditions['blog_content_id'] = $arguments['blog_content_id'];
            }

            if (!empty($arguments['keyword'])) {
                $conditions['keyword'] = $arguments['keyword'];
            }

            if (isset($arguments['status'])) {
                $conditions['status'] = $arguments['status'];
            }

            $limit = $arguments['limit'] ?? 10;
            $page = $arguments['page'] ?? 1;

            $conditions['limit'] = $limit;
            $conditions['page'] = $page;

            $results = $blogPostsService->getIndex($conditions)->toArray();

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

    /**
     * ブログ記事を取得
     */
    public function getBlogPost(array $arguments): array
    {
        try {
            $blogPostsService = $this->getService(BlogPostsServiceInterface::class);

            $result = $blogPostsService->get($arguments['id']);

            if ($result) {
                // ブログコンテンツIDが指定されている場合は条件をチェック
                if (!empty($arguments['blog_content_id']) &&
                    $result->blog_content_id != $arguments['blog_content_id']) {
                    return [
                        'error' => true,
                        'message' => '指定されたIDのブログ記事が見つかりません'
                    ];
                }

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

    /**
     * ブログ記事を編集
     */
    public function editBlogPost(array $arguments): array
    {
        try {
            $blogPostsService = $this->getService(BlogPostsServiceInterface::class);

            $entity = $blogPostsService->get($arguments['id']);

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
                try {
                    $usersService = $this->getService(UsersServiceInterface::class);
                    $conditions = ['email' => $arguments['email']];
                    $user = $usersService->getIndex($conditions)->first();
                    $data['user_id'] = $user ? $user->id : 1;
                } catch (\Exception $e) {
                    $data['user_id'] = 1; // エラー時はデフォルト
                }
            }

            $result = $blogPostsService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログ記事の更新に失敗しました'
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

    /**
     * ブログ記事を削除
     */
    public function deleteBlogPost(array $arguments): array
    {
        try {
            $blogPostsService = $this->getService(BlogPostsServiceInterface::class);

            $result = $blogPostsService->delete($arguments['id']);

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

    /**
     * ブログコンテンツIDを取得
     */
    protected function getBlogContentId(?string $blogContentName): int
    {
        if (empty($blogContentName)) {
            return 1; // デフォルト
        }

        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);
            $conditions = ['name' => $blogContentName];
            $content = $blogContentsService->getIndex($conditions)->first();

            return $content ? $content->id : 1;
        } catch (\Exception $e) {
            return 1; // エラー時はデフォルト
        }
    }

    /**
     * ブログカテゴリIDを取得
     */
    protected function getBlogCategoryId(string $categoryName, int $blogContentId): ?int
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);
            $conditions = [
                'name' => $categoryName,
                'blog_content_id' => $blogContentId
            ];
            $category = $blogCategoriesService->getIndex($conditions)->first();

            return $category ? $category->id : null;
        } catch (\Exception $e) {
            return null; // エラー時はnull
        }
    }
}
