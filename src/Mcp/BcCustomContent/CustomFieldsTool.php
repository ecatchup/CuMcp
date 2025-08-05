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
use PhpMcp\Server\ServerBuilder;

/**
 * カスタムフィールドツールクラス
 *
 * カスタムフィールドのCRUD操作を提供
 */
class CustomFieldsTool
{
    use BcContainerTrait;

    /**
     * カスタムフィールド関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addCustomField'],
                name: 'addCustomField',
                description: 'カスタムフィールドを追加します。typeには以下の値が指定可能: BcCcAutoZip, BcCcCheckbox, BcCcDate, BcCcDateTime, BcCcEmail, BcCcFile, BcCcHidden, BcCcMultiple, BcCcPassword, BcCcPref, BcCcRadio, BcCcRelated, BcCcSelect, BcCcTel, BcCcText, BcCcTextarea, BcCcWysiwyg, CuCcBurgerEditor（ブロックエディタ）',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'フィールド名（必須）'],
                        'title' => ['type' => 'string', 'description' => 'フィールドタイトル（必須）'],
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'BcCcAutoZip', 'BcCcCheckbox', 'BcCcDate', 'BcCcDateTime',
                                'BcCcEmail', 'BcCcFile', 'BcCcHidden', 'BcCcMultiple',
                                'BcCcPassword', 'BcCcPref', 'BcCcRadio', 'BcCcRelated',
                                'BcCcSelect', 'BcCcTel', 'BcCcText', 'BcCcTextarea',
                                'BcCcWysiwyg', 'CuCcBurgerEditor'
                            ],
                            'description' => 'フィールドタイプ（必須）'
                        ],
                        'source' => ['type' => 'string', 'description' => '選択肢（ラジオボタンやセレクトボックスの場合、改行で区切って指定する）']
                    ],
                    'required' => ['name', 'title', 'type']
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomFields'],
                name: 'getCustomFields',
                description: 'カスタムフィールドの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'フィールド名での絞り込み'],
                        'type' => ['type' => 'string', 'description' => 'フィールドタイプでの絞り込み'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 無効, 1: 有効）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getCustomField'],
                name: 'getCustomField',
                description: '指定されたIDのカスタムフィールドを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editCustomField'],
                name: 'editCustomField',
                description: 'カスタムフィールドを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）'],
                        'name' => ['type' => 'string', 'description' => 'フィールド名'],
                        'title' => ['type' => 'string', 'description' => 'フィールドタイトル'],
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'BcCcAutoZip', 'BcCcCheckbox', 'BcCcDate', 'BcCcDateTime',
                                'BcCcEmail', 'BcCcFile', 'BcCcHidden', 'BcCcMultiple',
                                'BcCcPassword', 'BcCcPref', 'BcCcRadio', 'BcCcRelated',
                                'BcCcSelect', 'BcCcTel', 'BcCcText', 'BcCcTextarea',
                                'BcCcWysiwyg', 'CuCcBurgerEditor'
                            ],
                            'description' => 'フィールドタイプ'
                        ],
                        'source' => ['type' => 'string', 'description' => '選択肢（ラジオボタンやセレクトボックスの場合、改行で区切って指定する）'],
                        'status' => ['type' => 'number', 'description' => 'ステータス（0: 無効, 1: 有効）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteCustomField'],
                name: 'deleteCustomField',
                description: '指定されたIDのカスタムフィールドを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カスタムフィールドID（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    /**
     * カスタムフィールドを追加
     */
    public function addCustomField(string $name, string $title, string $type, ?string $source = null): array
    {
        try {
            $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);

            $data = [
                'name' => $name,
                'title' => $title,
                'type' => $type,
                'source' => $source ?? null
            ];

            $result = $customFieldsService->create($data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムフィールドの保存に失敗しました'
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
     * カスタムフィールド一覧を取得
     */
    public function getCustomFields(?string $name = null, ?string $type = null, ?int $status = null): array
    {
        try {
            $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);

            $conditions = [];

            if (!empty($name)) {
                $conditions['name'] = $name;
            }

            if (!empty($type)) {
                $conditions['type'] = $type;
            }

            if (isset($status)) {
                $conditions['status'] = $status;
            }

            $results = $customFieldsService->getIndex($conditions)->toArray();

            return [
                'success' => true,
                'data' => $results
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
     * カスタムフィールドを取得
     */
    public function getCustomField(int $id): array
    {
        try {
            $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);

            $result = $customFieldsService->get($id);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムフィールドが見つかりません'
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
     * カスタムフィールドを編集
     */
    public function editCustomField(int $id, ?string $name = null, ?string $title = null, ?string $type = null, ?string $source = null, ?int $status = null): array
    {
        try {
            $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);

            $entity = $customFieldsService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのカスタムフィールドが見つかりません'
                ];
            }

            $data = [];
            if ($name !== null) $data['name'] = $name;
            if ($title !== null) $data['title'] = $title;
            if ($type !== null) $data['type'] = $type;
            if ($source !== null) $data['source'] = $source;
            if ($status !== null) $data['status'] = $status;

            $result = $customFieldsService->update($entity, $data);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムフィールドの更新に失敗しました'
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
     * カスタムフィールドを削除
     */
    public function deleteCustomField(int $id): array
    {
        try {
            $customFieldsService = $this->getService(CustomFieldsServiceInterface::class);

            $result = $customFieldsService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'カスタムフィールドを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'カスタムフィールドの削除に失敗しました'
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
