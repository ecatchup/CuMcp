<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BcCustomContent;

use BaserCore\Utility\BcContainerTrait;
use BcCustomContent\Service\CustomLinksServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * カスタムリンクツールクラス
 *
 * カスタムリンクのCRUD操作を提供
 */
class CustomLinksTool
{
    use BcContainerTrait;

    /**
     * カスタムリンク関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addCustomLink'],
                name: 'addCustomLink',
                description: 'カスタムリンクを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'カスタムリンク名（必須）'],
                        'title' => ['type' => 'string', 'description' => 'カスタムリンクのタイトル（必須）'],
                        'customTableId' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'customFieldId' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'useApi' => ['type' => 'boolean', 'description' => 'API使用'],
                        'searchTargetFront' => ['type' => 'boolean', 'description' => 'フロント検索対象'],
                        'searchTargetAdmin' => ['type' => 'boolean', 'description' => '管理画面検索対象'],
                        'displayFront' => ['type' => 'boolean', 'description' => 'フロント表示'],
                        'type' => ['type' => 'string', 'description' => 'タイプ']
                    ],
                    'required' => ['name', 'title', 'customTableId', 'customFieldId']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomLinks'],
                name: 'getCustomLinks',
                description: 'カスタムリンクの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'customTableId' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'customFieldId' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 無効, 1: 有効）'],
                        'type' => ['type' => 'string', 'description' => 'タイプでの絞り込み']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomLink'],
                name: 'getCustomLink',
                description: '指定されたIDのカスタムリンクを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムリンクID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomLink'],
                name: 'editCustomLink',
                description: '指定されたIDのカスタムリンクを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムリンクID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'カスタムリンク名'],
                        'title' => ['type' => 'string', 'description' => 'カスタムリンクのタイトル'],
                        'customTableId' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'customFieldId' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'useApi' => ['type' => 'boolean', 'description' => 'API使用'],
                        'searchTargetFront' => ['type' => 'boolean', 'description' => 'フロント検索対象'],
                        'searchTargetAdmin' => ['type' => 'boolean', 'description' => '管理画面検索対象'],
                        'displayFront' => ['type' => 'boolean', 'description' => 'フロント表示'],
                        'type' => ['type' => 'string', 'description' => 'タイプ']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomLink'],
                name: 'deleteCustomLink',
                description: '指定されたIDのカスタムリンクを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムリンクID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * カスタムリンクを追加
     */
    public function addCustomLink(string $name, string $title, int $customTableId, int $customFieldId, ?bool $status = null, ?bool $useApi = null, ?bool $searchTargetFront = null, ?bool $searchTargetAdmin = null, ?bool $displayFront = null, ?string $type = null): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $data = [
                'name' => $name,
                'title' => $title,
                'customTableId' => $customTableId,
                'customFieldId' => $customFieldId,
                'status' => $status,
                'useApi' => $useApi,
                'searchTargetFront' => $searchTargetFront,
                'searchTargetAdmin' => $searchTargetAdmin,
                'displayFront' => $displayFront,
                'type' => $type
            ];

            $result = $customLinksService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムリンクの保存に失敗しました'
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
     * カスタムリンク一覧を取得
     */
    public function getCustomLinks(?int $customTableId = null, ?int $customFieldId = null, ?string $keyword = null, ?int $status = null, ?string $type = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $conditions = [];

            if (!empty($customFieldId)) {
                $conditions['customFieldId'] = $customFieldId;
            }

            if (!empty($keyword)) {
                $conditions['keyword'] = $keyword;
            }

            if (isset($status)) {
                $conditions['status'] = $status;
            }

            if (!empty($type)) {
                $conditions['type'] = $type;
            }

            if (!empty($limit)) {
                $conditions['limit'] = $limit;
            }

            if (!empty($page)) {
                $conditions['page'] = $page;
            }

            // CustomLinksService::getIndex() は custom_table_id を最初の引数として期待している
            $results = $customLinksService->getIndex($customTableId ?? 1, $conditions)->toArray();

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
     * カスタムリンクを取得
     */
    public function getCustomLink(int $id): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $result = $customLinksService->get($id);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムリンクが見つかりません'
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
     * カスタムリンクを編集
     */
    public function editCustomLink(int $id, ?string $name = null, ?string $title = null, ?int $customTableId = null, ?int $customFieldId = null, ?bool $status = null, ?bool $useApi = null, ?bool $searchTargetFront = null, ?bool $searchTargetAdmin = null, ?bool $displayFront = null, ?string $type = null): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $entity = $customLinksService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムリンクが見つかりません'
                ];
            }

            $data = [];
            if ($name !== null) $data['name'] = $name;
            if ($title !== null) $data['title'] = $title;
            if ($customTableId !== null) $data['customTableId'] = $customTableId;
            if ($customFieldId !== null) $data['customFieldId'] = $customFieldId;
            if ($status !== null) $data['status'] = $status;
            if ($useApi !== null) $data['useApi'] = $useApi;
            if ($searchTargetFront !== null) $data['searchTargetFront'] = $searchTargetFront;
            if ($searchTargetAdmin !== null) $data['searchTargetAdmin'] = $searchTargetAdmin;
            if ($displayFront !== null) $data['displayFront'] = $displayFront;
            if ($type !== null) $data['type'] = $type;

            $result = $customLinksService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムリンクの更新に失敗しました'
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
     * カスタムリンクを削除
     */
    public function deleteCustomLink(int $id): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $result = $customLinksService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムリンクを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムリンクの削除に失敗しました'
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
