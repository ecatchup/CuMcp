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

namespace CuMcp\Mcp\BcCustomContent;

use BaserCore\Utility\BcContainerTrait;
use BcCustomContent\Service\CustomContentsServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * カスタムコンテンツツールクラス
 *
 * カスタムコンテンツのCRUD操作を提供
 */
class CustomContentsTool
{
    use BcContainerTrait;

    /**
     * カスタムコンテンツ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addCustomContent'],
                name: 'addCustomContent',
                description: 'カスタムコンテンツを追加します。カスタムコンテンツを追加するにはカスタムテーブルのIDが必要です。事前に作成するか既存のカスタムテーブルIDを指定してください。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'カスタムコンテンツ名、URLに影響します（必須）'],
                        'title' => ['type' => 'string', 'description' => 'カスタムコンテンツのタイトル（必須）'],
                        'customTableId' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'siteId' => ['type' => 'number', 'default' => 1, 'description' => 'サイトID（初期値: 1）'],
                        'parentId' => ['type' => 'number', 'default' => 1, 'description' => '親フォルダID（初期値: 1）'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'default' => 'default', 'description' => 'テンプレート名（初期値: default）'],
                        'listCount' => ['type' => 'number', 'default' => 10, 'description' => 'リスト表示件数（初期値: 10）'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'DESC', 'description' => 'リスト表示方向（ASC|DESC、初期値: DESC）'],
                        'listOrder' => ['type' => 'string', 'default' => 'id', 'description' => 'リスト表示順序（初期値: id）'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）']
                    ],
                    'required' => ['name', 'title', 'customTableId']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomContents'],
                name: 'getCustomContents',
                description: 'カスタムコンテンツの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'customTableId' => ['type' => 'number', 'description' => 'カスタムテーブルID'],
                        'siteId' => ['type' => 'number', 'description' => 'サイトID'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomContent'],
                name: 'getCustomContent',
                description: '指定されたIDのカスタムコンテンツを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムコンテンツID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomContent'],
                name: 'editCustomContent',
                description: '指定されたIDのカスタムコンテンツを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムコンテンツID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'カスタムコンテンツ名'],
                        'title' => ['type' => 'string', 'description' => 'カスタムコンテンツのタイトル'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'description' => 'テンプレート名'],
                        'listCount' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'listDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'listOrder' => ['type' => 'string', 'description' => 'リスト表示順序'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomContent'],
                name: 'deleteCustomContent',
                description: '指定されたIDのカスタムコンテンツを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムコンテンツID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * カスタムコンテンツを追加
     */
    public function addCustomContent(string $name, string $title, int $customTableId, ?int $siteId = 1, ?int $parentId = 1, ?string $description = null, ?string $template = 'default', ?int $listCount = 10, ?string $listDirection = 'DESC', ?string $listOrder = 'id', ?int $status = null): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            // Content entity data structure required by baserCMS
            $data = [
                'name' => $name,
                'title' => $title,
                'customTableId' => $customTableId,
                'siteId' => $siteId,
                'parentId' => $parentId,
                'description' => $description,
                'template' => $template,
                'listCount' => $listCount,
                'listDirection' => $listDirection,
                'listOrder' => $listOrder,
                'status' => $status,
                'content' => [
                    'name' => $name,
                    'plugin' => 'BcCustomContent',
                    'type' => 'CustomContent',
                    'title' => $title,
                    'description' => $description ?? '',
                    'siteId' => $siteId,
                    'parentId' => $parentId,
                    'status' => $status ?? true,
                    'authorId' => 1,
                    'layoutTemplate' => '',
                    'excludeSearch' => false,
                    'selfStatus' => true,
                    'siteRoot' => false,
                    'excludeMenu' => false,
                    'blankLink' => false
                ]
            ];

            $result = $customContentsService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムコンテンツの保存に失敗しました'
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
     * カスタムコンテンツ一覧を取得
     */
    public function getCustomContents(?int $customTableId = null, ?int $siteId = null, ?string $keyword = null, ?int $status = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            $conditions = [];

            if (!empty($customTableId)) {
                $conditions['customTableId'] = $customTableId;
            }

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

            $results = $customContentsService->getIndex($conditions)->toArray();

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
     * カスタムコンテンツを取得
     */
    public function getCustomContent(int $id): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            $result = $customContentsService->get($id);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムコンテンツが見つかりません'
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
     * カスタムコンテンツを編集
     */
    public function editCustomContent(int $id, ?string $name = null, ?string $title = null, ?string $description = null, ?string $template = null, ?int $listCount = null, ?string $listDirection = null, ?string $listOrder = null, ?int $status = null): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            $entity = $customContentsService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムコンテンツが見つかりません'
                ];
            }

            $data = [];
            if ($name !== null) $data['name'] = $name;
            if ($title !== null) $data['title'] = $title;
            if ($description !== null) $data['description'] = $description;
            if ($template !== null) $data['template'] = $template;
            if ($listCount !== null) $data['listCount'] = $listCount;
            if ($listDirection !== null) $data['listDirection'] = $listDirection;
            if ($listOrder !== null) $data['listOrder'] = $listOrder;
            if ($status !== null) $data['status'] = $status;

            // Include Content entity updates when necessary
            if ($name !== null || $title !== null || $description !== null || $status !== null) {
                $contentData = [];
                if ($name !== null) $contentData['name'] = $name;
                if ($title !== null) $contentData['title'] = $title;
                if ($description !== null) $contentData['description'] = $description;
                if ($status !== null) $contentData['status'] = $status;
                
                $data['content'] = $contentData;
            }

            $result = $customContentsService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムコンテンツの更新に失敗しました'
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
     * カスタムコンテンツを削除
     */
    public function deleteCustomContent(int $id): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            $result = $customContentsService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムコンテンツを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムコンテンツの削除に失敗しました'
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
