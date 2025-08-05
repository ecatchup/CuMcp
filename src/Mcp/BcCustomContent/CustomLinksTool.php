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

namespace CuMcp\McpServer\BcCustomContent;

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
    public function addCustomLink(array $arguments): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $data = array_intersect_key($arguments, array_flip([
                'name', 'title', 'custom_table_id', 'custom_field_id', 'status',
                'use_api', 'search_target_front', 'search_target_admin',
                'display_front', 'type'
            ]));

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
    public function getCustomLinks(array $arguments): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $conditions = [];

            if (!empty($arguments['custom_table_id'])) {
                $conditions['custom_table_id'] = $arguments['custom_table_id'];
            }

            if (!empty($arguments['custom_field_id'])) {
                $conditions['custom_field_id'] = $arguments['custom_field_id'];
            }

            if (!empty($arguments['keyword'])) {
                $conditions['keyword'] = $arguments['keyword'];
            }

            if (isset($arguments['status'])) {
                $conditions['status'] = $arguments['status'];
            }

            if (!empty($arguments['type'])) {
                $conditions['type'] = $arguments['type'];
            }

            if (!empty($arguments['limit'])) {
                $conditions['limit'] = $arguments['limit'];
            }

            if (!empty($arguments['page'])) {
                $conditions['page'] = $arguments['page'];
            }

            $results = $customLinksService->getIndex($conditions)->toArray();

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
     * カスタムリンクを取得
     */
    public function getCustomLink(array $arguments): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $result = $customLinksService->get($arguments['id']);

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
    public function editCustomLink(array $arguments): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $entity = $customLinksService->get($arguments['id']);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムリンクが見つかりません'
                ];
            }

            $data = array_intersect_key($arguments, array_flip([
                'name', 'title', 'custom_table_id', 'custom_field_id', 'status',
                'use_api', 'search_target_front', 'search_target_admin',
                'display_front', 'type'
            ]));

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
    public function deleteCustomLink(array $arguments): array
    {
        try {
            $customLinksService = $this->getService(CustomLinksServiceInterface::class);

            $result = $customLinksService->delete($arguments['id']);

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
