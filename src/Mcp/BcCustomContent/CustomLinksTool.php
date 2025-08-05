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
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'custom_field_id' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'use_api' => ['type' => 'boolean', 'description' => 'API使用'],
                        'search_target_front' => ['type' => 'boolean', 'description' => 'フロント検索対象'],
                        'search_target_admin' => ['type' => 'boolean', 'description' => '管理画面検索対象'],
                        'display_front' => ['type' => 'boolean', 'description' => 'フロント表示'],
                        'type' => ['type' => 'string', 'description' => 'タイプ']
                    ],
                    'required' => ['name', 'title', 'custom_table_id', 'custom_field_id']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomLinks'],
                name: 'getCustomLinks',
                description: 'カスタムリンクの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID'],
                        'custom_field_id' => ['type' => 'number', 'description' => 'カスタムフィールドID'],
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
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID'],
                        'custom_field_id' => ['type' => 'number', 'description' => 'カスタムフィールドID'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'use_api' => ['type' => 'boolean', 'description' => 'API使用'],
                        'search_target_front' => ['type' => 'boolean', 'description' => 'フロント検索対象'],
                        'search_target_admin' => ['type' => 'boolean', 'description' => '管理画面検索対象'],
                        'display_front' => ['type' => 'boolean', 'description' => 'フロント表示'],
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
    public function addCustomLink(string $name, string $title, int $custom_table_id, int $custom_field_id, ?bool $status = null, ?bool $use_api = null, ?bool $search_target_front = null, ?bool $search_target_admin = null, ?bool $display_front = null, ?string $type = null): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $data = [
                'name' => $name,
                'title' => $title,
                'custom_table_id' => $custom_table_id,
                'custom_field_id' => $custom_field_id,
                'status' => $status,
                'use_api' => $use_api,
                'search_target_front' => $search_target_front,
                'search_target_admin' => $search_target_admin,
                'display_front' => $display_front,
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
    public function getCustomLinks(?int $custom_table_id = null, ?int $custom_field_id = null, ?string $keyword = null, ?int $status = null, ?string $type = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $conditions = [];

            if (!empty($custom_field_id)) {
                $conditions['custom_field_id'] = $custom_field_id;
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
            $results = $customLinksService->getIndex($custom_table_id ?? 1, $conditions)->toArray();

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
    public function editCustomLink(int $id, ?string $name = null, ?string $title = null, ?int $custom_table_id = null, ?int $custom_field_id = null, ?bool $status = null, ?bool $use_api = null, ?bool $search_target_front = null, ?bool $search_target_admin = null, ?bool $display_front = null, ?string $type = null): array
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
            if ($custom_table_id !== null) $data['custom_table_id'] = $custom_table_id;
            if ($custom_field_id !== null) $data['custom_field_id'] = $custom_field_id;
            if ($status !== null) $data['status'] = $status;
            if ($use_api !== null) $data['use_api'] = $use_api;
            if ($search_target_front !== null) $data['search_target_front'] = $search_target_front;
            if ($search_target_admin !== null) $data['search_target_admin'] = $search_target_admin;
            if ($display_front !== null) $data['display_front'] = $display_front;
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
