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
use BcCustomContent\Service\CustomTablesServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * カスタムテーブルツールクラス
 *
 * カスタムテーブルのCRUD操作を提供
 */
class CustomTablesTool
{
    use BcContainerTrait;

    /**
     * カスタムテーブル関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addCustomTable'],
                name: 'addCustomTable',
                description: 'カスタムテーブルを追加し、指定されたカスタムフィールドを関連付けます。フィールドを関連付けるためには、事前にカスタムフィールドが作成されている必要があります。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'テーブル名（必須）'],
                        'title' => ['type' => 'string', 'description' => 'テーブルタイトル（必須）'],
                        'custom_field_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => '関連付けるカスタムフィールドの名前配列'
                        ]
                    ],
                    'required' => ['name', 'title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomTables'],
                name: 'getCustomTables',
                description: 'カスタムテーブルの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'type' => ['type' => 'string', 'description' => 'テーブルタイプ']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomTable'],
                name: 'getCustomTable',
                description: '指定されたIDのカスタムテーブルを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomTable'],
                name: 'editCustomTable',
                description: '指定されたIDのカスタムテーブルを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'テーブル名'],
                        'title' => ['type' => 'string', 'description' => 'テーブルタイトル'],
                        'type' => ['type' => 'string', 'description' => 'テーブルタイプ'],
                        'display_field' => ['type' => 'string', 'description' => '表示フィールド'],
                        'has_child' => ['type' => 'number', 'description' => '子テーブルを持つかどうか（0: 持たない, 1: 持つ）'],
                        'custom_field_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => '関連付けるカスタムフィールドの名前配列'
                        ]
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomTable'],
                name: 'deleteCustomTable',
                description: '指定されたIDのカスタムテーブルを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * カスタムテーブルを追加
     */
    public function addCustomTable(array $arguments): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $data = [
                'name' => $arguments['name'],
                'title' => $arguments['title']
            ];

            $result = $customTablesService->create($data);

            if ($result && !empty($arguments['custom_field_names'])) {
                // カスタムフィールドとの関連付け
                foreach ($arguments['custom_field_names'] as $fieldName) {
                    $customTablesService->addCustomFieldLink($result->id, $fieldName);
                }
            }

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムテーブルの保存に失敗しました'
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
     * カスタムテーブル一覧を取得
     */
    public function getCustomTables(array $arguments): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $conditions = [];

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

            $results = $customTablesService->getIndex($conditions)->toArray();

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
     * カスタムテーブルを取得
     */
    public function getCustomTable(array $arguments): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $result = $customTablesService->get($arguments['id']);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムテーブルが見つかりません'
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
     * カスタムテーブルを編集
     */
    public function editCustomTable(array $arguments): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $entity = $customTablesService->get($arguments['id']);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムテーブルが見つかりません'
                ];
            }

            $data = array_intersect_key($arguments, array_flip([
                'name', 'title', 'type', 'display_field', 'has_child'
            ]));

            $result = $customTablesService->update($entity, $data);

            // カスタムフィールドとの関連付けを更新
            if (!empty($arguments['custom_field_names'])) {
                // 既存の関連を削除
                $customTablesService->removeAllCustomFieldLinks($arguments['id']);
                // 新しい関連を追加
                foreach ($arguments['custom_field_names'] as $fieldName) {
                    $customTablesService->addCustomFieldLink($arguments['id'], $fieldName);
                }
            }

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムテーブルの更新に失敗しました'
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
     * カスタムテーブルを削除
     */
    public function deleteCustomTable(array $arguments): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $result = $customTablesService->delete($arguments['id']);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムテーブルを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムテーブルの削除に失敗しました'
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
