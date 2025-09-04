<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BcBlog;

use BaserCore\Utility\BcUtil;
use Cake\Core\Configure;
use CuMcp\Mcp\BaseMcpTool;
use BcBlog\Service\BlogContentsServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログコンテンツツールクラス
 *
 * ブログコンテンツのCRUD操作を提供
 */
class BlogContentsTool extends BaseMcpTool
{

    /**
     * ブログコンテンツ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addBlogContent'],
                name: 'addBlogContent',
                description: 'baserCMSは複数のブログを持つことができます。一つ一つのブログをブログコンテンツと呼び、そのブログコンテンツを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'ブログコンテンツ名、URLに影響します（必須）'],
                        'title' => ['type' => 'string', 'description' => 'ブログコンテンツのタイトル（必須）'],
                        'siteId' => ['type' => 'number', 'description' => 'サイトID（省略時は1）'],
                        'parentId' => ['type' => 'number', 'description' => '親ID（省略時は1）'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名（省略時は "default"）'],
                        'listCount' => ['type' => 'number', 'description' => '一覧表示件数（省略時は10）'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => '一覧表示方向（ASC|DESC）、（省略時はDESC）'],
                        'feedCount' => ['type' => 'number', 'description' => 'RSSフィードに表示する件数（省略時は10）'],
                        'commentUse' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか（省略時はfalse）'],
                        'commentApprove' => ['type' => 'boolean', 'description' => 'コメント機能について各コメントの公開について承認制にするか（省略時はfalse）'],
                        'tagUse' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか（省略時はfalse）'],
                        'eyeCatchSizeThumbWidth' => ['type' => 'number', 'description' => 'アイキャッチサムネイル幅（PC）（省略時はシステムデフォルト値）'],
                        'eyeCatchSizeThumbHeight' => ['type' => 'number', 'description' => 'アイキャッチサムネイル高さ（PC）（省略時はシステムデフォルト値）'],
                        'eyeCatchSizeMobileThumbWidth' => ['type' => 'number', 'description' => 'アイキャッチサムネイル幅（モバイル）（省略時はシステムデフォルト値）'],
                        'eyeCatchSizeMobileThumbHeight' => ['type' => 'number', 'description' => 'アイキャッチサムネイル高さ（モバイル）（省略時はシステムデフォルト値）'],
                        'useContent' => ['type' => 'boolean', 'description' => '概要入力欄を使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）、（省略時は0）'],
                        'widgetArea' => ['type' => 'number', 'description' => 'ウィジェットエリアID']
                    ],
                    'required' => ['name', 'title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogContents'],
                name: 'getBlogContents',
                description: 'baserCMSは複数のブログを持つことができます。一つ一つのブログをブログコンテンツと呼び、そのブログコンテンツの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'title' => ['type' => 'string', 'description' => 'ブログコンテンツのタイトル（部分一致）'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（null: 全て, publish: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogContent'],
                name: 'getBlogContent',
                description: 'baserCMSは複数のブログを持つことができます。一つ一つのブログをブログコンテンツと呼び、指定されたIDのブログコンテンツを取得します',
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
                description: 'baserCMSは複数のブログを持つことができます。一つ一つのブログをブログコンテンツと呼び、指定されたIDのブログコンテンツを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'ブログコンテンツID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'ブログコンテンツ名、URLに影響します（必須）'],
                        'title' => ['type' => 'string', 'description' => 'ブログコンテンツのタイトル（必須）'],
                        'siteId' => ['type' => 'number', 'description' => 'サイトID'],
                        'parentId' => ['type' => 'number', 'description' => '親ID'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名'],
                        'listCount' => ['type' => 'number', 'description' => '一覧表示件数'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => '一覧表示方向（ASC|DESC）'],
                        'feedCount' => ['type' => 'number', 'description' => 'RSSフィードに表示する件数'],
                        'commentUse' => ['type' => 'boolean', 'description' => 'コメント機能を使用するか'],
                        'commentApprove' => ['type' => 'boolean', 'description' => 'コメント機能について各コメントの公開について承認制にするか'],
                        'tagUse' => ['type' => 'boolean', 'description' => 'タグ機能を使用するか'],
                        'eyeCatchSizeThumbWidth' => ['type' => 'number', 'description' => 'アイキャッチサムネイル幅（PC）'],
                        'eyeCatchSizeThumbHeight' => ['type' => 'number', 'description' => 'アイキャッチサムネイル高さ（PC）'],
                        'eyeCatchSizeMobileThumbWidth' => ['type' => 'number', 'description' => 'アイキャッチサムネイル幅（モバイル）'],
                        'eyeCatchSizeMobileThumbHeight' => ['type' => 'number', 'description' => 'アイキャッチサムネイル高さ（モバイル）'],
                        'useContent' => ['type' => 'boolean', 'description' => '概要入力欄を使用するか'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）'],
                        'widgetArea' => ['type' => 'number', 'description' => 'ウィジェットエリアID']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogContent'],
                name: 'deleteBlogContent',
                description: 'baserCMSは複数のブログを持つことができます。一つ一つのブログをブログコンテンツと呼び、指定されたIDのブログコンテンツを削除します',
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
    public function addBlogContent(
        string $name,
        string $title,
        ?int $siteId = 1,
        ?int $parentId = 1,
        ?string $description = null,
        ?string $template = 'default',
        ?int $listCount = 10,
        ?string $listDirection = 'DESC',
        ?int $feedCount = 10,
        ?bool $commentUse = false,
        ?bool $commentApprove = false,
        ?bool $tagUse = false,
        ?int $eyeCatchSizeThumbWidth = null,
        ?int $eyeCatchSizeThumbHeight = null,
        ?int $eyeCatchSizeMobileThumbWidth = null,
        ?int $eyeCatchSizeMobileThumbHeight = null,
        ?bool $useContent = false,
        ?int $status = 0,
        ?int $widgetArea = null
    ): array {
        return $this->executeWithErrorHandling(function() use (
            $name,
            $title,
            $siteId,
            $parentId,
            $description,
            $template,
            $listCount,
            $listDirection,
            $feedCount,
            $commentUse,
            $commentApprove,
            $tagUse,
            $eyeCatchSizeThumbWidth,
            $eyeCatchSizeThumbHeight,
            $eyeCatchSizeMobileThumbWidth,
            $eyeCatchSizeMobileThumbHeight,
            $useContent,
            $status,
            $widgetArea
        ) {
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
                'eye_catch_size_thumb_width' => $eyeCatchSizeThumbWidth?? Configure::read('BcBlog.eye_catch_size_thumb_width'),
                'eye_catch_size_thumb_height' => $eyeCatchSizeThumbHeight?? Configure::read('BcBlog.eye_catch_size_thumb_height'),
                'eye_catch_size_mobile_thumb_width' => $eyeCatchSizeMobileThumbWidth?? Configure::read('BcBlog.eye_catch_size_mobile_thumb_width'),
                'eye_catch_size_mobile_thumb_height' => $eyeCatchSizeMobileThumbHeight?? Configure::read('BcBlog.eye_catch_size_mobile_thumb_height'),
                'use_content' => $useContent,
                'widget_area' => $widgetArea
            ];

            // Contentデータを含めた統合データ構造
            $data = array_merge($blogContentData, [
                'content' => $contentData
            ]);

            $result = $blogContentsService->create($data);

            if ($result) {
                return $this->createSuccessResponse($result->toArray());
            } else {
                return $this->createErrorResponse('ブログコンテンツの保存に失敗しました');
            }
        });
    }

    /**
     * ブログコンテンツ一覧を取得
     */
    public function getBlogContents(
        ?string $title = null,
        ?int $status = null,
        ?int $limit = null,
        ?int $page = null
    ): array
    {
        return $this->executeWithErrorHandling(function() use ($title, $status, $limit, $page) {
            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $conditions = [];

            if (!empty($title)) $conditions['title'] = $title;
            if (isset($status)) $conditions['status'] = $status;
            if (!empty($limit)) $conditions['limit'] = $limit;
            if (!empty($page)) $conditions['page'] = $page;

            $results = $blogContentsService->getIndex($conditions)->toArray();

            return $this->createSuccessResponse([
                'data' => $results,
                'pagination' => [
                    'page' => $page ?? 1,
                    'limit' => $limit ?? null,
                    'count' => count($results)
                ]
            ]);
        });
    }

    /**
     * ブログコンテンツを取得
     */
    public function getBlogContent(int $id): array
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            // 必須パラメータのチェック
            if (empty($id)) {
                return $this->createErrorResponse('IDは必須です');
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->get($id);

            if ($result) {
                return $this->createSuccessResponse($result->toArray());
            } else {
                return $this->createErrorResponse('指定されたIDのブログコンテンツが見つかりません');
            }
        });
    }

    /**
     * ブログコンテンツを編集
     */
    public function editBlogContent(
        int $id,
        ?string $name = null,
        ?string $title = null,
        ?int $siteId = null,
        ?int $parentId = null,
        ?string $description = null,
        ?string $template = null,
        ?int $listCount = null,
        ?string $listDirection = null,
        ?int $feedCount = null,
        ?bool $commentUse = null,
        ?bool $commentApprove = null,
        ?bool $tagUse = null,
        ?int $eyeCatchSizeThumbWidth = null,
        ?int $eyeCatchSizeThumbHeight = null,
        ?int $eyeCatchSizeMobileThumbWidth = null,
        ?int $eyeCatchSizeMobileThumbHeight = null,
        ?bool $useContent = null,
        ?int $status = null,
        ?int $widgetArea = null): array
    {
        return $this->executeWithErrorHandling(function() use (
            $id,
            $name,
            $title,
            $siteId,
            $parentId,
            $description,
            $template,
            $listCount,
            $listDirection,
            $feedCount,
            $commentUse,
            $commentApprove,
            $tagUse,
            $eyeCatchSizeThumbWidth,
            $eyeCatchSizeThumbHeight,
            $eyeCatchSizeMobileThumbWidth,
            $eyeCatchSizeMobileThumbHeight,
            $useContent,
            $status,
            $widgetArea
        ) {
            // 必須パラメータのチェック
            if (empty($id)) {
                return $this->createErrorResponse('IDは必須です');
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $entity = $blogContentsService->get($id);

            if (!$entity) {
                return $this->createErrorResponse('指定されたIDのブログコンテンツが見つかりません');
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
            if($eyeCatchSizeThumbWidth !== null) $data['eye_catch_size_thumb_width'] = $eyeCatchSizeThumbWidth;
            if($eyeCatchSizeThumbHeight !== null) $data['eye_catch_size_thumb_height'] = $eyeCatchSizeThumbHeight;
            if($eyeCatchSizeMobileThumbWidth !== null) $data['eye_catch_size_mobile_thumb_width'] = $eyeCatchSizeMobileThumbWidth;
            if($eyeCatchSizeMobileThumbHeight !== null) $data['eye_catch_size_mobile_thumb_height'] = $eyeCatchSizeMobileThumbHeight;
            if ($useContent !== null) $data['use_content'] = $useContent;
            if ($widgetArea !== null) $data['widget_area'] = $widgetArea;

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
                return $this->createSuccessResponse($result->toArray());
            } else {
                return $this->createErrorResponse('ブログコンテンツの更新に失敗しました');
            }
        });
    }

    /**
     * ブログコンテンツを削除
     */
    public function deleteBlogContent(int $id): array
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            // 必須パラメータのチェック
            if (empty($id)) {
                return $this->createErrorResponse('IDは必須です');
            }

            $blogContentsService = $this->getService(BlogContentsServiceInterface::class);

            $result = $blogContentsService->delete($id);

            if ($result) {
                return $this->createSuccessResponse('ブログコンテンツを削除しました');
            } else {
                return $this->createErrorResponse('ブログコンテンツの削除に失敗しました');
            }
        });
    }
}
