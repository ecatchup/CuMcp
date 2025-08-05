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
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID（必須）'],
                        'site_id' => ['type' => 'number', 'default' => 1, 'description' => 'サイトID（初期値: 1）'],
                        'parent_id' => ['type' => 'number', 'default' => 1, 'description' => '親フォルダID（初期値: 1）'],
                        'description' => ['type' => 'string', 'description' => '説明文'],
                        'template' => ['type' => 'string', 'default' => 'default', 'description' => 'テンプレート名（初期値: default）'],
                        'list_count' => ['type' => 'number', 'default' => 10, 'description' => 'リスト表示件数（初期値: 10）'],
                        'list_direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'DESC', 'description' => 'リスト表示方向（ASC|DESC、初期値: DESC）'],
                        'list_order' => ['type' => 'string', 'default' => 'id', 'description' => 'リスト表示順序（初期値: id）'],
                        'status' => ['type' => 'number', 'description' => '公開状態（0: 非公開状態, 1: 公開状態）']
                    ],
                    'required' => ['name', 'title', 'custom_table_id']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomContents'],
                name: 'getCustomContents',
                description: 'カスタムコンテンツの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'custom_table_id' => ['type' => 'number', 'description' => 'カスタムテーブルID'],
                        'site_id' => ['type' => 'number', 'description' => 'サイトID'],
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
                        'list_count' => ['type' => 'number', 'description' => 'リスト表示件数'],
                        'list_direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'リスト表示方向（ASC|DESC）'],
                        'list_order' => ['type' => 'string', 'description' => 'リスト表示順序'],
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
    public function addCustomContent(string $name, string $title, int $custom_table_id, ?int $site_id = 1, ?int $parent_id = 1, ?string $description = null, ?string $template = 'default', ?int $list_count = 10, ?string $list_direction = 'DESC', ?string $list_order = 'id', ?int $status = null): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            // Content entity data structure required by baserCMS
            $data = [
                'name' => $name,
                'title' => $title,
                'custom_table_id' => $custom_table_id,
                'site_id' => $site_id,
                'parent_id' => $parent_id,
                'description' => $description,
                'template' => $template,
                'list_count' => $list_count,
                'list_direction' => $list_direction,
                'list_order' => $list_order,
                'status' => $status,
                'content' => [
                    'name' => $name,
                    'plugin' => 'BcCustomContent',
                    'type' => 'CustomContent',
                    'title' => $title,
                    'description' => $description ?? '',
                    'site_id' => $site_id,
                    'parent_id' => $parent_id,
                    'status' => $status ?? true,
                    'author_id' => 1,
                    'layout_template' => '',
                    'exclude_search' => false,
                    'self_status' => true,
                    'site_root' => false,
                    'exclude_menu' => false,
                    'blank_link' => false
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
    public function getCustomContents(?int $custom_table_id = null, ?int $site_id = null, ?string $keyword = null, ?int $status = null, ?int $limit = null, ?int $page = 1): array
    {
        try {
            $customContentsService = $this->getService(CustomContentsServiceInterface::class);

            $conditions = [];

            if (!empty($custom_table_id)) {
                $conditions['custom_table_id'] = $custom_table_id;
            }

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
    public function editCustomContent(int $id, ?string $name = null, ?string $title = null, ?string $description = null, ?string $template = null, ?int $list_count = null, ?string $list_direction = null, ?string $list_order = null, ?int $status = null): array
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
            if ($list_count !== null) $data['list_count'] = $list_count;
            if ($list_direction !== null) $data['list_direction'] = $list_direction;
            if ($list_order !== null) $data['list_order'] = $list_order;
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
