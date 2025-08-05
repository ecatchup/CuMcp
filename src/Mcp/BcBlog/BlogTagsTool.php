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
use BcBlog\Service\BlogTagsServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログタグツールクラス
 *
 * ブログタグのCRUD操作を提供
 */
class BlogTagsTool
{
    use BcContainerTrait;

    /**
     * ブログタグ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addBlogTag'],
                name: 'addBlogTag',
                description: 'ブログタグを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'タグ名（必須）']
                    ],
                    'required' => ['name']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogTags'],
                name: 'getBlogTags',
                description: 'ブログタグの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'name' => ['type' => 'string', 'description' => 'タグ名での検索']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogTag'],
                name: 'getBlogTag',
                description: '指定されたIDのブログタグを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログタグID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editBlogTag'],
                name: 'editBlogTag',
                description: '指定されたIDのブログタグを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログタグID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'タグ名（必須）']
                    ],
                    'required' => ['id', 'name']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogTag'],
                name: 'deleteBlogTag',
                description: '指定されたIDのブログタグを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログタグID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * ブログタグを追加
     */
    public function addBlogTag(string $name): array
    {
        try {
            $blogTagsService = $this->getService(BlogTagsServiceInterface::class);

            $data = [
                'name' => $name
            ];

            $result = $blogTagsService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログタグの保存に失敗しました'
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
     * ブログタグ一覧を取得
     */
    public function getBlogTags(?string $keyword = null, ?string $name = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $blogTagsService = $this->getService(BlogTagsServiceInterface::class);

            $conditions = [];

            if (!empty($keyword)) {
                $conditions['keyword'] = $keyword;
            }

            if (!empty($name)) {
                $conditions['name'] = $name;
            }

            if (!empty($limit)) {
                $conditions['limit'] = $limit;
            }

            if (!empty($page)) {
                $conditions['page'] = $page;
            }

            $results = $blogTagsService->getIndex($conditions)->toArray();

            return [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'page' => $page ?? 1,
                    'limit' => $limit ?? null,
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
     * ブログタグを取得
     */
    public function getBlogTag(int $id): array
    {
        try {
            $blogTagsService = $this->getService(BlogTagsServiceInterface::class);

            $result = $blogTagsService->get($id);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログタグが見つかりません'
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
     * ブログタグを編集
     */
    public function editBlogTag(int $id, string $name): array
    {
        try {
            $blogTagsService = $this->getService(BlogTagsServiceInterface::class);

            $entity = $blogTagsService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログタグが見つかりません'
                ];
            }

            $data = [
                'name' => $name
            ];

            $result = $blogTagsService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログタグの更新に失敗しました'
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
     * ブログタグを削除
     */
    public function deleteBlogTag(int $id): array
    {
        try {
            $blogTagsService = $this->getService(BlogTagsServiceInterface::class);

            $result = $blogTagsService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ブログタグを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログタグの削除に失敗しました'
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
