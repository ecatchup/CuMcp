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
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parent_id' => ['type' => 'number', 'description' => '親カテゴリID（省略時はルートカテゴリ）'],
                        'status' => ['type' => 'number', 'default' => 1, 'description' => '公開ステータス（0: 非公開, 1: 公開）'],
                        'lft' => ['type' => 'number', 'description' => '左値（省略時は自動設定）'],
                        'rght' => ['type' => 'number', 'description' => '右値（省略時は自動設定）']
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
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
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
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
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
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）'],
                        'parent_id' => ['type' => 'number', 'description' => '親カテゴリID'],
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
                        'blog_content_id' => ['type' => 'number', 'description' => 'ブログコンテンツID（省略時はデフォルト）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    public function addBlogCategory(string $title, ?string $name = null, ?int $blog_content_id = 1, ?int $parent_id = null, ?int $status = 1, ?int $lft = null, ?int $rght = null): array
    {
        // デバッグ情報をログに出力
        error_log("addBlogCategory called with title: $title, blog_content_id: $blog_content_id");
        
        try {
            $blogCategoriesService = $this->getService(BlogCategoriesServiceInterface::class);

            // 必須パラメータのチェック
            if (empty($title)) {
                return [
                    'error' => true,
                    'message' => 'titleは必須です'
                ];
            }

            // nameが指定されていない場合、タイトルからスラッグを生成
            if (empty($name)) {
                $name = $this->generateSlug($title);
            }

            $data = [
                'title' => $title,
                'name' => $name,
                'parent_id' => $parent_id,
                'status' => $status,
                'lft' => $lft,
                'rght' => $rght
            ];

            // BlogCategoriesService::create() は blog_content_id を最初の引数として期待している
            $result = $blogCategoriesService->create($blog_content_id, $data);

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

    public function getBlogCategories(?int $blog_content_id = 1, ?int $limit = null, ?int $page = null, ?string $keyword = null, ?int $status = null): array
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
            $results = $blogCategoriesService->getIndex($blog_content_id ?? 1, $conditions)->toArray();

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

    public function getBlogCategory(int $id, ?int $blog_content_id = null): array
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
                if (!empty($blog_content_id) &&
                    $result->blog_content_id != $blog_content_id) {
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

    public function editBlogCategory(int $id, ?string $title = null, ?string $name = null, ?int $blog_content_id = null, ?int $parent_id = null, ?int $status = null, ?int $lft = null, ?int $rght = null): array
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
            if ($blog_content_id !== null) $data['blog_content_id'] = $blog_content_id;
            if ($parent_id !== null) $data['parent_id'] = $parent_id;
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

    public function deleteBlogCategory(int $id, ?int $blog_content_id = null): array
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
            if (!empty($blog_content_id) && $entity->blog_content_id != $blog_content_id) {
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

    /**
     * タイトルからURLスラッグを生成
     */
    private function generateSlug(string $title): string
    {
        // 日本語文字を英数字に変換（簡易版）
        $slug = mb_convert_kana($title, 'a', 'UTF-8'); // ひらがなをカタカナに
        
        // 一般的な日本語をローマ字に変換（基本的なもののみ）
        $replacements = [
            'テスト' => 'test',
            'サンプル' => 'sample',
            'カテゴリ' => 'category',
            'ブログ' => 'blog',
            'ニュース' => 'news',
            '記事' => 'article'
        ];
        
        foreach ($replacements as $japanese => $english) {
            $slug = str_replace($japanese, $english, $slug);
        }
        
        // 日本語が残っている場合は、ランダムな文字列を生成
        if (preg_match('/[^\x00-\x7F]/', $slug)) {
            $slug = 'category_' . uniqid();
        }
        
        // 英数字、ハイフン、アンダースコアのみにする
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $slug);
        $slug = preg_replace('/_{2,}/', '_', $slug); // 連続するアンダースコアを1つに
        $slug = trim($slug, '_'); // 前後のアンダースコアを削除
        
        return $slug ?: 'category_' . uniqid();
    }
}
