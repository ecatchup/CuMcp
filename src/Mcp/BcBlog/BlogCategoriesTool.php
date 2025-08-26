<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BcBlog;

use BaserCore\Utility\BcContainerTrait;
use BcBlog\Service\BlogCategoriesServiceInterface;
use PhpMcp\Server\ServerBuilder;

/**
 * ブログカテゴリツールクラス
 *
 * ブログカテゴリのCRUD操作を提供
 */
class BlogCategoriesTool
{
    use BcContainerTrait;

    /**
     * ブログカテゴリ関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'addBlogCategory'],
                name: 'addBlogCategory',
                description: 'ブログカテゴリを追加します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'カテゴリタイトル（必須）'],
                        'name' => ['type' => 'string', 'description' => 'カテゴリ名（省略時はタイトルから自動生成）'],
                        'blogContentId' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parentId' => ['type' => 'number', 'description' => '親カテゴリID（省略時はルートカテゴリ）'],
                        'status' => ['type' => 'number', 'default' => 1, 'description' => '公開ステータス（0: 非公開, 1: 公開）']
                    ],
                    'required' => ['title']
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogCategories'],
                name: 'getBlogCategories',
                description: 'ブログカテゴリの一覧を取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'blogContentId' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'limit' => ['type' => 'number', 'description' => '取得件数（省略時は制限なし）'],
                        'page' => ['type' => 'number', 'description' => 'ページ番号（省略時は1ページ目）'],
                        'keyword' => ['type' => 'string', 'description' => '検索キーワード'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）']
                    ]
                ]
            )
            ->withTool(
                handler: [self::class, 'getBlogCategory'],
                name: 'getBlogCategory',
                description: '指定されたIDのブログカテゴリを取得します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'blogContentId' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'editBlogCategory'],
                name: 'editBlogCategory',
                description: '指定されたIDのブログカテゴリを編集します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'title' => ['type' => 'string', 'description' => 'カテゴリタイトル'],
                        'name' => ['type' => 'string', 'description' => 'カテゴリ名'],
                        'blogContentId' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parentId' => ['type' => 'number', 'description' => '親カテゴリID（省略時はルートカテゴリ）'],
                        'status' => ['type' => 'number', 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'lft' => ['type' => 'number', 'description' => '左値'],
                        'rght' => ['type' => 'number', 'description' => '右値']
                    ],
                    'required' => ['id']
                ]
            )
            ->withTool(
                handler: [self::class, 'deleteBlogCategory'],
                name: 'deleteBlogCategory',
                description: '指定されたIDのブログカテゴリを削除します',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'カテゴリID（必須）'],
                        'blogContentId' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    public function addBlogCategory(
        string $title, ?string
        $name = null, ?int
        $blogContentId = 1, ?int
        $parentId = null, ?int
        $status = 1
    ): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            // 必須パラメータのチェック
            if (empty($title)) {
                return [
                    'error' => true,
                    'message' => 'titleは必須です'
                ];
            }

            $result = $blogCategoriesService->create($blogContentId, [
                'title' => $title,
                'name' => $name?? 'category_' . uniqid(),
                'parent_id' => $parentId,
                'status' => $status
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの保存に失敗しました'
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

    public function getBlogCategories(?int $blogContentId = 1, ?int $limit = null, ?int $page = null, ?string $keyword = null, ?int $status = null): array
    {
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $conditions = [];
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

            // BlogCategoriesService::getIndex() は blog_content_id を最初の引数として期待している
            $results = $blogCategoriesService->getIndex($blogContentId ?? 1, $conditions)->toArray();

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

    public function getBlogCategory(int $id, ?int $blogContentId = null): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'idは必須です'
                ];
            }

            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $result = $blogCategoriesService->get($id);

            if ($result) {
                // ブログコンテンツIDが指定されている場合は条件をチェック
                if (!empty($blogContentId) &&
                    $result->blog_content_id != $blogContentId) {
                    return [
                        'error' => true,
                        'message' => '指定されたIDのブログカテゴリが見つかりません'
                    ];
                }

                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
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

    public function editBlogCategory(int $id, ?string $title = null, ?string $name = null, ?int $blogContentId = null, ?int $parentId = null, ?int $status = null, ?int $lft = null, ?int $rght = null): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'idは必須です'
                ];
            }

            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $entity = $blogCategoriesService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
                ];
            }

            // 更新データを構築（null以外の値のみ）
            $data = [];
            if ($title !== null) $data['title'] = $title;
            if ($name !== null) $data['name'] = $name;
            if ($blogContentId !== null) $data['blog_content_id'] = $blogContentId;
            if ($parentId !== null) $data['parent_id'] = $parentId;
            if ($status !== null) $data['status'] = $status;
            if ($lft !== null) $data['lft'] = $lft;
            if ($rght !== null) $data['rght'] = $rght;

            // nameを更新する場合、バリデーションエラーを避けるために
            // 現在のblog_content_idを明示的に含める
            if (isset($data['name']) && !isset($data['blog_content_id'])) {
                $data['blog_content_id'] = $entity->blog_content_id;
            }

            // バリデーションコンテキストを設定
            $options = [];
            if (isset($data['name'])) {
                $options['validate'] = false; // 重複チェックのバリデーションを一時的に無効化
            }

            // バリデーションを無効化した場合は手動で重複チェックを実行
            if (isset($data['name']) && isset($options['validate']) && $options['validate'] === false) {
                // 同じblog_content_id内での重複をチェック
                $existingCategory = $blogCategoriesService->getIndex($entity->blog_content_id, [
                    'name' => $data['name']
                ])->first();

                if ($existingCategory && $existingCategory->id !== $id) {
                    return [
                        'error' => true,
                        'message' => '指定されたカテゴリ名は既に使用されています'
                    ];
                }
            }

            $result = $blogCategoriesService->update($entity, $data, $options);

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result->toArray()
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの更新に失敗しました'
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

    public function deleteBlogCategory(int $id, ?int $blogContentId = null): array
    {
        try {
            // 必須パラメータのチェック
            if (empty($id)) {
                return [
                    'error' => true,
                    'message' => 'idは必須です'
                ];
            }

            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            $entity = $blogCategoriesService->get($id);

            if (!$entity) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
                ];
            }

            // ブログコンテンツIDが指定されている場合は条件をチェック
            if (!empty($blogContentId) && $entity->blog_content_id != $blogContentId) {
                return [
                    'error' => true,
                    'message' => '指定されたIDのブログカテゴリが見つかりません'
                ];
            }

            // BlogCategoriesService::delete() は ID を期待している
            $result = $blogCategoriesService->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ブログカテゴリを削除しました'
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'ブログカテゴリの削除に失敗しました'
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
