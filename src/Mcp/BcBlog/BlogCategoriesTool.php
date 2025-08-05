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
use BcBlog\Service\BlogCategoriesServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログカテゴリツールクラス
 *
 * ブログカテゴリのCRUD操作を提供
 */
class BlogCategoriesTool
{
    use BcContainerTrait;

    /**
     * ブログカテゴリ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addBlogCategory'],
                name: 'addBlogCategory',
                description: 'ブログカテゴリを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'カテゴリタイトル（必須）'],
                        'name' => ['type' => 'string', 'description' => 'カテゴリ名（省略時はタイトルから自動生成）'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parent_id' => ['type' => 'number', 'description' => '親カテゴリID（省略時はルートカテゴリ）'],
                        'status' => ['type' => 'number', 'default' => 1, 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'lft' => ['type' => 'number', 'description' => '左値（省略時は自動設定）'],
                        'rght' => ['type' => 'number', 'description' => '右値（省略時は自動設定）']
                    ],
                    'required' => ['title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogCategories'],
                name: 'getBlogCategories',
                description: 'ブログカテゴリの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogCategory'],
                name: 'getBlogCategory',
                description: '指定されたIDのブログカテゴリを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editBlogCategory'],
                name: 'editBlogCategory',
                description: '指定されたIDのブログカテゴリを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'カテゴリタイトル'],
                        'name' => ['type' => 'string', 'description' => 'カテゴリ名'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parent_id' => ['type' => 'number', 'description' => '親カテゴリID'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'lft' => ['type' => 'number', 'description' => '左値'],
                        'rght' => ['type' => 'number', 'description' => '右値']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogCategory'],
                name: 'deleteBlogCategory',
                description: '指定されたIDのブログカテゴリを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * ブログカテゴリを追加
     */
    public function addBlogCategory(array $arguments): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $data = [
                'title' => $arguments['title'],
                'name' => $arguments['name'] ?? null,
                'blog_content_id' => $arguments['blog_content_id'] ?? 1,
                'parent_id' => $arguments['parent_id'] ?? null,
                'status' => $arguments['status'] ?? 1,
                'lft' => $arguments['lft'] ?? null,
                'rght' => $arguments['rght'] ?? null
            ];

            $result = $blogCategoriesService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの保存に失敗しました'
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
     * ブログカテゴリ一覧を取得
     */
    public function getBlogCategories(array $arguments): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

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

            if (!empty($arguments['limit'])) {
                $conditions['limit'] = $arguments['limit'];
            }

            if (!empty($arguments['page'])) {
                $conditions['page'] = $arguments['page'];
            }

            $results = $blogCategoriesService->getIndex($conditions)->toArray();

            return [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'page' => $arguments['page'] ?? 1,
                    'limit' => $arguments['limit'] ?? null,
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
     * ブログカテゴリを取得
     */
    public function getBlogCategory(array $arguments): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $result = $blogCategoriesService->get($arguments['id']);

            if ($result) {
                // ブログコンテンツIDが指定されている場合は条件をチェック
                if (!empty($arguments['blog_content_id']) &&
                    $result->blog_content_id != $arguments['blog_content_id']) {
                    return [
                        'error' => true,
                        'message' => '指定されたIDのブログカテゴリが見つかりません'
                    ];
                }

                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
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
     * ブログカテゴリを編集
     */
    public function editBlogCategory(array $arguments): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $entity = $blogCategoriesService->get($arguments['id']);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
                ];
            }

            $data = array_intersect_key($arguments, array_flip([
                'title', 'name', 'blog_content_id', 'parent_id', 'status', 'lft', 'rght'
            ]));

            $result = $blogCategoriesService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの更新に失敗しました'
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
     * ブログカテゴリを削除
     */
    public function deleteBlogCategory(array $arguments): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $result = $blogCategoriesService->delete($arguments['id']);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ブログカテゴリを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの削除に失敗しました'
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
}
