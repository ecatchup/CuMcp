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
    public function addBlogContent(string $name, string $title, ?int $site_id = 1, ?int $parent_id = 1, ?string $description = null, ?string $template = 'default', ?int $list_count = 10, ?string $list_direction = 'DESC', ?int $feed_count = 10, ?bool $comment_use = false, ?bool $comment_approve = false, ?bool $tag_use = false, ?string $eye_catch_size = null, ?bool $use_content = false, ?int $status = 1, ?int $widget_area = null): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            // baserCMSでは、BlogContentとContentの両方を作成する必要があります
            // Contentエンティティの基本データ
            $contentData = [
                'name' => $name,
                'plugin' => 'BcBlog',
                'type' => 'BlogContent',
                'title' => $title,
                'description' => $description ?? '',
                'site_id' => $site_id,
                'parent_id' => $parent_id,
                'status' => (bool)$status,
                'author_id' => 1, // デフォルトユーザー
                'layout_template' => '',
                'exclude_search' => false,
                'self_status' => true,
                'site_root' => false,
                'exclude_menu' => false,
                'blank_link' => false
            ];

            // BlogContentエンティティの基本データ
            $blogContentData = [
                'description' => $description ?? '',
                'template' => $template,
                'list_count' => $list_count,
                'list_direction' => $list_direction,
                'feed_count' => $feed_count,
                'comment_use' => $comment_use,
                'comment_approve' => $comment_approve,
                'tag_use' => $tag_use,
                'eye_catch_size' => $eye_catch_size,
                'use_content' => $use_content,
                'widget_area' => $widget_area
            ];

            // Contentデータを含めた統合データ構造
            $data = array_merge($blogContentData, [
                'content' => $contentData
            ]);

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
    public function getBlogContents(?int $site_id = null, ?string $keyword = null, ?int $status = null, ?int $limit = null, ?int $page = null): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $conditions = [];

            if (!empty($site_id)) {
                $conditions['site_id'] = $site_id;
            }

            if (!empty($keyword)) {
                $conditions['keyword'] = $keyword;
            }

            if (isset($status)) {
                $conditions['status'] = $status;
            }

            if (!empty($limit)) {
                $conditions['limit'] = $limit;
            }

            if (!empty($page)) {
                $conditions['page'] = $page;
            }

            $results = $blogContentsService->getIndex($conditions)->toArray();

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
     * ブログコンテンツを取得
     */
    public function getBlogContent(int $id): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->get($id);

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
    public function editBlogContent(int $id, ?string $name = null, ?string $title = null, ?string $description = null, ?string $template = null, ?int $list_count = null, ?string $list_direction = null, ?int $feed_count = null, ?bool $comment_use = null, ?bool $comment_approve = null, ?bool $tag_use = null, ?string $eye_catch_size = null, ?bool $use_content = null, ?int $status = null, ?int $widget_area = null): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $entity = $blogContentsService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログコンテンツが見つかりません'
                ];
            }

            // 更新データを構築（null以外の値のみ）
            $data = [];
            if ($description !== null) $data['description'] = $description;
            if ($template !== null) $data['template'] = $template;
            if ($list_count !== null) $data['list_count'] = $list_count;
            if ($list_direction !== null) $data['list_direction'] = $list_direction;
            if ($feed_count !== null) $data['feed_count'] = $feed_count;
            if ($comment_use !== null) $data['comment_use'] = $comment_use;
            if ($comment_approve !== null) $data['comment_approve'] = $comment_approve;
            if ($tag_use !== null) $data['tag_use'] = $tag_use;
            if ($eye_catch_size !== null) $data['eye_catch_size'] = $eye_catch_size;
            if ($use_content !== null) $data['use_content'] = $use_content;
            if ($widget_area !== null) $data['widget_area'] = $widget_area;

            // Contentエンティティの更新データも含める（もし関連するContentフィールドが変更される場合）
            $contentData = [];
            if ($name !== null) $contentData['name'] = $name;
            if ($title !== null) $contentData['title'] = $title;
            if ($description !== null) $contentData['description'] = $description;
            if ($status !== null) $contentData['status'] = (bool)$status;

            if (!empty($contentData)) {
                $data['content'] = $contentData;
            }

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
    public function deleteBlogContent(int $id): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->delete($id);

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
