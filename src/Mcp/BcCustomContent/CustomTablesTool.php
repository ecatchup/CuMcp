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
use BcCustomContent\Service\CustomFieldsServiceInterface;
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
                        'customFieldNames' => [
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
                        'displayField' => ['type' => 'string', 'description' => '表示フィールド'],
                        'hasChild' => ['type' => 'number', 'description' => '子テーブルを持つかどうか（0: 持たない, 1: 持つ）'],
                        'customFieldNames' => [
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
    public function addCustomTable(string $name, string $title, ?array $customFieldNames = null): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $data = [
                'name' => $name,
                'title' => $title
            ];

            $result = $customTablesService->create($data);

            if ($result && !empty($customFieldNames)) {
                // カスタムフィールドとの関連付け
                $customLinks = $this->createCustomLinks($customFieldNames);
                if($customLinks) {
                    $customTable = $result->toArray();
                    $customTable['custom_links'] = $customLinks;
                    $result = $customTablesService->update($result, $customTable);
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

    private function createCustomLinks($customFieldNames)
    {
        $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);
        $customLinks = [];
        if (!empty($customFieldNames)) {
            $i = 0;
            foreach ($customFieldNames as $fieldName) {
                $customField = $customFieldsService->getIndex(['name' => $fieldName])->first();
                if ($customField) {
                    $customLinks["new_" . $i + 1] = [
                        "name" => $customField->name,
                        "custom_field_id" => $customField->id,
                        "type" => $customField->type,
                        "display_front" => true,
                        "use_api" => true,
                        "status" => true,
                        "title" => $customField->title,
                        "search_target_admin" => true,
                        "search_target_front" => true
                    ];
                    $i++;
                }
            }
        }
        return $customLinks;
    }
    /**
     * カスタムテーブル一覧を取得
     */
    public function getCustomTables(?string $keyword = null, ?int $status = null, ?string $type = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $conditions = [];

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

            $results = $customTablesService->getIndex($conditions)->toArray();

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
     * カスタムテーブルを取得
     */
    public function getCustomTable(int $id): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $result = $customTablesService->get($id);

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
    public function editCustomTable(int $id, ?string $name = null, ?string $title = null, ?string $type = null, ?string $displayField = null, ?int $hasChild = null, ?array $customFieldNames = null): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $entity = $customTablesService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムテーブルが見つかりません'
                ];
            }

            $data = [];
            if ($name !== null) $data['name'] = $name;
            if ($title !== null) $data['title'] = $title;
            if ($type !== null) $data['type'] = $type;
            if ($displayField !== null) $data['displayField'] = $displayField;
            if ($hasChild !== null) $data['hasChild'] = $hasChild;

            $result = $customTablesService->update($entity, $data);

            // カスタムフィールドとの関連付けを更新
            if ($result && !empty($customFieldNames)) {
                // カスタムフィールドとの関連付け
                $customLinks = $this->createCustomLinks($customFieldNames);
                if($customLinks) {
                    $customTable = $result->toArray();
                    $customTable['custom_links'] = $customLinks;
                    $result = $customTablesService->update($result, $customTable);
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
    public function deleteCustomTable(int $id): array
    {
        try {
            $customTablesService = $this->getService(CustomTablesServiceInterface::class);

            $result = $customTablesService->delete($id);

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
