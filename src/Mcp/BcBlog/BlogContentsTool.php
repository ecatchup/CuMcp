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
use BcBlog\Service\BlogContentsServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログコンテンツツールクラス
 *
 * ブログコンテンツのCRUD操作を提供
 */
class BlogContentsTool
{
    use BcContainerTrait;

    /**
     * ブログコンテンツ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addBlogContent'],
                name: 'addBlogContent',
                description: 'ブログコンテンツを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'ブログコンテンツ名、URLに影響します（必須）'],
                        'title' => ['type' => 'string', 'description' => 'ブログコンテンツのタイトル（必須）'],
                        'site_id' => ['type' => 'number', 'description' => 'サイトID'],
                        'parent_id' => ['type' => 'number', 'description' => '親ID'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名'],
                        'list_count' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'list_direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'feed_count' => ['type' => 'number', 'description' => 'フィード件数'],
                        'comment_use' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか'],
                        'comment_approve' => ['type' => 'boolean', 'description' => 'コメント承認制にするか'],
                        'tag_use' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか'],
                        'eye_catch_size' => ['type' => 'string', 'description' => 'アイキャッチサイズ'],
                        'use_content' => ['type' => 'boolean', 'description' => 'コンテンツを使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）'],
                        'widget_area' => ['type' => 'number', 'description' => 'ウィジェットエリア']
                    ],
                    'required' => ['name', 'title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogContents'],
                name: 'getBlogContents',
                description: 'ブログコンテンツの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'site_id' => ['type' => 'number', 'description' => 'サイトID'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 非公開, 1: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogContent'],
                name: 'getBlogContent',
                description: '指定されたIDのブログコンテンツを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログコンテンツID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editBlogContent'],
                name: 'editBlogContent',
                description: '指定されたIDのブログコンテンツを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログコンテンツID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'ブログコンテンツ名'],
                        'title' => ['type' => 'string', 'description' => 'ブログコンテンツのタイトル'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名'],
                        'list_count' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'list_direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'feed_count' => ['type' => 'number', 'description' => 'フィード件数'],
                        'comment_use' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか'],
                        'comment_approve' => ['type' => 'boolean', 'description' => 'コメント承認制にするか'],
                        'tag_use' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか'],
                        'eye_catch_size' => ['type' => 'string', 'description' => 'アイキャッチサイズ'],
                        'use_content' => ['type' => 'boolean', 'description' => 'コンテンツを使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）'],
                        'widget_area' => ['type' => 'number', 'description' => 'ウィジェットエリア']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogContent'],
                name: 'deleteBlogContent',
                description: '指定されたIDのブログコンテンツを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログコンテンツID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * ブログコンテンツを追加
     */
    public function addBlogContent(array $arguments): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $data = array_intersect_key($arguments, array_flip([
                'name', 'title', 'site_id', 'parent_id', 'description', 'template',
                'list_count', 'list_direction', 'feed_count', 'comment_use',
                'comment_approve', 'tag_use', 'eye_catch_size', 'use_content',
                'status', 'widget_area'
            ]));

            $result = $blogContentsService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログコンテンツの保存に失敗しました'
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
     * ブログコンテンツ一覧を取得
     */
    public function getBlogContents(array $arguments): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $conditions = [];

            if (!empty($arguments['site_id'])) {
                $conditions['site_id'] = $arguments['site_id'];
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

            $results = $blogContentsService->getIndex($conditions)->toArray();

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
     * ブログコンテンツを取得
     */
    public function getBlogContent(array $arguments): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->get($arguments['id']);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログコンテンツが見つかりません'
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
     * ブログコンテンツを編集
     */
    public function editBlogContent(array $arguments): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $entity = $blogContentsService->get($arguments['id']);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログコンテンツが見つかりません'
                ];
            }

            $data = array_intersect_key($arguments, array_flip([
                'name', 'title', 'description', 'template', 'list_count',
                'list_direction', 'feed_count', 'comment_use', 'comment_approve',
                'tag_use', 'eye_catch_size', 'use_content', 'status', 'widget_area'
            ]));

            $result = $blogContentsService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログコンテンツの更新に失敗しました'
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
     * ブログコンテンツを削除
     */
    public function deleteBlogContent(array $arguments): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->delete($arguments['id']);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ブログコンテンツを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログコンテンツの削除に失敗しました'
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
