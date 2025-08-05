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

use BcCustomContent\Service\CustomEntriesServiceInterface;
use PhpMcp\Server\ServerBuilder;
use BaserCore\Utility\BcContainerTrait;

/**
 * カスタムエントリーツールクラス
 *
 * カスタムエントリーのCRUD操作を提供
 */
class CustomEntriesTool
{
    use BcContainerTrait;
    /**
     * カスタムエントリー関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addCustomEntry'],
                name: 'addCustomEntry',
                description: 'カスタムエントリーを追加します。カスタムエントリーを追加するには、カスタムテーブルが必要です。事前に作成するか既存のカスタムテーブルIDを指定してください。フロントエンドに表示させるには、カスタムテーブルがカスタムコンテンツと紐づいている必要があります。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'タイトル（必須）'],
                        'name' => ['type' => 'string', 'default' => '', 'description' => 'スラッグ（初期値空文字）'],
                        'status' => ['type' => 'boolean', 'default' => false, 'description' => '公開状態（デフォルト：false）'],
                        'published' => ['type' => 'string', 'description' => '公開日（YYYY-MM-DD HH:mm:ss形式、省略時は当日）'],
                        'publish_begin' => ['type' => 'string', 'description' => '公開開始日（YYYY-MM-DD HH:mm:ss形式、省略可）'],
                        'publish_end' => ['type' => 'string', 'description' => '公開終了日（YYYY-MM-DD HH:mm:ss形式、省略可）'],
                        'creator_id' => ['type' => 'number', 'default' => 1, 'description' => '投稿者ID（デフォルト初期ユーザー）'],
                        'custom_fields' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'カスタムフィールドの値（フィールド名をキーとするオブジェクト）、ファイルアップロードのフィールドの場合は、参照が可能なファイルのパスを指定します'
                        ]
                    ],
                    'required' => ['custom_table_id', 'title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomEntries'],
                name: 'getCustomEntries',
                description: 'カスタムエントリーの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'limit' => ['type' => 'number', 'default' => 20, 'description' => '取得件数（デフォルト: 20）'],
                        'page' => ['type' => 'number', 'default' => 1, 'description' => 'ページ番号（デフォルト: 1）'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 非公開, 1: 公開）']
                    ],
                    'required' => ['custom_table_id']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomEntry'],
                name: 'getCustomEntry',
                description: '指定されたIDのカスタムエントリーを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）']
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomEntry'],
                name: 'editCustomEntry',
                description: '指定されたIDのカスタムエントリーを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'タイトル'],
                        'name' => ['type' => 'string', 'description' => 'スラッグ'],
                        'status' => ['type' => 'boolean', 'description' => '公開状態'],
                        'published' => ['type' => 'string', 'description' => '公開日（YYYY-MM-DD HH:mm:ss形式）'],
                        'publish_begin' => ['type' => 'string', 'description' => '公開開始日（YYYY-MM-DD HH:mm:ss形式）'],
                        'publish_end' => ['type' => 'string', 'description' => '公開終了日（YYYY-MM-DD HH:mm:ss形式）'],
                        'creator_id' => ['type' => 'number', 'description' => '投稿者ID'],
                        'custom_fields' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'カスタムフィールドの値（フィールド名をキーとするオブジェクト）'
                        ]
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomEntry'],
                name: 'deleteCustomEntry',
                description: '指定されたIDのカスタムエントリーを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'id' => ['type' => 'number', 'description' => 'カスタムエントリーID（必須）']
                    ],
                    'required' => ['custom_table_id', 'id']
                ]
            );
    }

    /**
     * カスタムエントリーを追加
     */
    public function addCustomEntry(int $custom_table_id, string $title, ?string $name = '', ?bool $status = false, ?string $published = null, ?string $publish_begin = null, ?string $publish_end = null, ?int $creator_id = 1, ?array $custom_fields = null): array
    {
        try {
            $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);

            $data = [
                'custom_table_id' => $custom_table_id,
                'title' => $title,
                'name' => $name ?? '',
                'status' => $status ?? false,
                'published' => $published ?? date('Y-m-d H:i:s'),
                'publish_begin' => $publish_begin ?? null,
                'publish_end' => $publish_end ?? null,
                'creator_id' => $creator_id ?? 1
            ];

            // カスタムフィールドの値を追加
            if (!empty($custom_fields)) {
                $data = array_merge($data, $custom_fields);
            }

            $result = $customEntriesService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの保存に失敗しました'
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
     * カスタムエントリー一覧を取得
     */
    public function getCustomEntries(int $custom_table_id, ?int $limit = 20, ?int $page = 1, ?int $status = null): array
    {
        try {
            $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);

            $conditions = [
                'custom_table_id' => $custom_table_id,
                'limit' => $limit ?? 20,
                'page' => $page ?? 1
            ];

            if (isset($status)) {
                $conditions['status'] = $status;
            }

            $results = $customEntriesService->getIndex($conditions)->toArray();

            return [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'page' => $conditions['page'],
                    'limit' => $conditions['limit'],
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
     * カスタムエントリーを取得
     */
    public function getCustomEntry(int $custom_table_id, int $id): array
    {
        try {
            $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);

            $result = $customEntriesService->get($id, [
                'custom_table_id' => $custom_table_id
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムエントリーが見つかりません'
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
     * カスタムエントリーを編集
     */
    public function editCustomEntry(int $custom_table_id, int $id, ?string $title = null, ?string $name = null, ?bool $status = null, ?string $published = null, ?string $publish_begin = null, ?string $publish_end = null, ?int $creator_id = null, ?array $custom_fields = null): array
    {
        try {
            $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);

            $entity = $customEntriesService->get($id, [
                'custom_table_id' => $custom_table_id
            ]);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムエントリーが見つかりません'
                ];
            }

            $data = [];
            if ($title !== null) $data['title'] = $title;
            if ($name !== null) $data['name'] = $name;
            if ($status !== null) $data['status'] = $status;
            if ($published !== null) $data['published'] = $published;
            if ($publish_begin !== null) $data['publish_begin'] = $publish_begin;
            if ($publish_end !== null) $data['publish_end'] = $publish_end;
            if ($creator_id !== null) $data['creator_id'] = $creator_id;

            // カスタムフィールドの値を追加
            if (!empty($custom_fields)) {
                $data = array_merge($data, $custom_fields);
            }

            $result = $customEntriesService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの更新に失敗しました'
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
     * カスタムエントリーを削除
     */
    public function deleteCustomEntry(int $custom_table_id, int $id): array
    {
        try {
            $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);

            $result = $customEntriesService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムエントリーを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムエントリーの削除に失敗しました'
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
