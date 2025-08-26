<?php
declare(strict_types=1);

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
                        'siteId' => ['type' => 'number', 'description' => 'サイトID'],
                        'parentId' => ['type' => 'number', 'description' => '親ID'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名'],
                        'listCount' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'feedCount' => ['type' => 'number', 'description' => 'フィード件数'],
                        'commentUse' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか'],
                        'commentApprove' => ['type' => 'boolean', 'description' => 'コメント承認制にするか'],
                        'tagUse' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか'],
                        'eyeCatchSize' => ['type' => 'string', 'description' => 'アイキャッチサイズ'],
                        'useContent' => ['type' => 'boolean', 'description' => 'コンテンツを使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）'],
                        'widgetArea' => ['type' => 'number', 'description' => 'ウィジェットエリア']
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
                        'siteId' => ['type' => 'number', 'description' => 'サイトID'],
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
                        'listCount' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'feedCount' => ['type' => 'number', 'description' => 'フィード件数'],
                        'commentUse' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか'],
                        'commentApprove' => ['type' => 'boolean', 'description' => 'コメント承認制にするか'],
                        'tagUse' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか'],
                        'eyeCatchSize' => ['type' => 'string', 'description' => 'アイキャッチサイズ'],
                        'useContent' => ['type' => 'boolean', 'description' => 'コンテンツを使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）'],
                        'widgetArea' => ['type' => 'number', 'description' => 'ウィジェットエリア']
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
    public function addBlogContent(string $name, string $title, ?int $siteId = 1, ?int $parentId = 1, ?string $description = null, ?string $template = 'default', ?int $listCount = 10, ?string $listDirection = 'DESC', ?int $feedCount = 10, ?bool $commentUse = false, ?bool $commentApprove = false, ?bool $tagUse = false, ?string $eyeCatchSize = null, ?bool $useContent = false, ?int $status = 1, ?int $widgetArea = null): array
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
                'site_id' => $siteId,
                'parent_id' => $parentId,
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
                'list_count' => $listCount,
                'list_direction' => $listDirection,
                'feed_count' => $feedCount,
                'comment_use' => $commentUse,
                'comment_approve' => $commentApprove,
                'tag_use' => $tagUse,
                'eye_catch_size' => $eyeCatchSize,
                'use_content' => $useContent,
                'widgetArea' => $widgetArea
            ];

            // Contentデータを含めた統合データ構造
            $data = array_merge($blogContentData, [
                'content' => $contentData
            ]);

            $result = $blogContentsService->create($data);

            if ($result) {
                return ['isError' => false,
                    'content' => $result->toArray()
                ];
            } else {
                return ['isError' => true,
                    'content' => 'ブログコンテンツの保存に失敗しました'
                ];
            }
        } catch (\Exception $e) {
            return ['isError' => true,
                    'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * ブログコンテンツ一覧を取得
     */
    public function getBlogContents(?int $siteId = null, ?string $keyword = null, ?int $status = null, ?int $limit = null, ?int $page = null): array
    {
        try {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $conditions = [];

            if (!empty($siteId)) {
                $conditions['siteId'] = $siteId;
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

            return ['isError' => false,
                    'content' => $results,
                'pagination' => [
                    'page' => $page ?? 1,
                    'limit' => $limit ?? null,
                    'count' => count($results)
                ]
            ];
        } catch (\Exception $e) {
            return ['isError' => true,
                    'content' => $e->getMessage(),
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
                return ['isError' => true,
                    'content' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->get($id);

            if ($result) {
                return ['isError' => false,
                    'content' => $result->toArray()
                ];
            } else {
                return ['isError' => true,
                    'content' => '指定されたIDのブログコンテンツが見つかりません'
                ];
            }
        } catch (\Exception $e) {
            return ['isError' => true,
                    'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * ブログコンテンツを編集
     */
    public function editBlogContent(int $id, ?string $name = null, ?string $title = null, ?string $description = null, ?string $template = null, ?int $listCount = null, ?string $listDirection = null, ?int $feedCount = null, ?bool $commentUse = null, ?bool $commentApprove = null, ?bool $tagUse = null, ?string $eyeCatchSize = null, ?bool $useContent = null, ?int $status = null, ?int $widgetArea = null): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return ['isError' => true,
                    'content' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $entity = $blogContentsService->get($id);

            if (!$entity) {
                return ['isError' => true,
                    'content' => '指定されたIDのブログコンテンツが見つかりません'
                ];
            }

            // 更新データを構築（null以外の値のみ）
            $data = [];
            if ($description !== null) $data['description'] = $description;
            if ($template !== null) $data['template'] = $template;
            if ($listCount !== null) $data['listCount'] = $listCount;
            if ($listDirection !== null) $data['listDirection'] = $listDirection;
            if ($feedCount !== null) $data['feedCount'] = $feedCount;
            if ($commentUse !== null) $data['commentUse'] = $commentUse;
            if ($commentApprove !== null) $data['commentApprove'] = $commentApprove;
            if ($tagUse !== null) $data['tagUse'] = $tagUse;
            if ($eyeCatchSize !== null) $data['eyeCatchSize'] = $eyeCatchSize;
            if ($useContent !== null) $data['useContent'] = $useContent;
            if ($widgetArea !== null) $data['widgetArea'] = $widgetArea;

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
                return ['isError' => false,
                    'content' => $result->toArray()
                ];
            } else {
                return ['isError' => true,
                    'content' => 'ブログコンテンツの更新に失敗しました'
                ];
            }
        } catch (\Exception $e) {
            return ['isError' => true,
                    'content' => $e->getMessage(),
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
                return ['isError' => true,
                    'content' => 'IDは必須です'
                ];
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->delete($id);

            if ($result) {
                return ['isError' => false,
                    'content' => 'ブログコンテンツを削除しました'
                ];
            } else {
                return ['isError' => true,
                    'content' => 'ブログコンテンツの削除に失敗しました'
                ];
            }
        } catch (\Exception $e) {
            return ['isError' => true,
                    'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
