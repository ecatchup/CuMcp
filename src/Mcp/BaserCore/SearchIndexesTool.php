<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BaserCore;

use Cake\Log\LogTrait;
use CuMcp\Mcp\BcBlog\BlogPostsTool;
use PhpMcp\Server\ServerBuilder;

/**
 * 検索インデックスツールクラス
 */
class SearchIndexesTool
{
    use LogTrait;
    private BlogPostsTool $blogPostsTool;

    public function __construct()
    {
        $this->blogPostsTool = new BlogPostsTool();
    }

    /**
     * ブログ記事関連のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'search'],
                name: 'search',
                description: 'クエリ文字列でブログ記事を検索します。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => '検索クエリ']
                    ],
                    'required' => ['query']
                ]
            )->withTool(
                handler: [self::class, 'fetch'],
                name: 'fetch',
                description: '識別子を指定してデータを取得します。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => '識別子（必須）']
                    ],
                    'required' => ['id']
                ]
            );
    }

    public function fetch(string $id): array
    {
        $result = $this->blogPostsTool->getBlogPost((int)$id, 1);
        if (!empty($result['success'])) {
            $result['data'] = [
                'id' => $result['data']['id'],
                'title' => $result['data']['title'],
                'text' => $result['data']['content'] . $result['data']['detail'],
                'url' => ''
            ];
        }
        return $result;
    }

    public function search(string $query): array
    {
        $result = $this->blogPostsTool->getBlogPosts(1, $query);
        if (!empty($result['success'])) {
            $postsArray = [];
            foreach($result['data'] as $post) {
                $postsArray[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'text' => ($post->detail ?? '') . ($post->content ?? ''),
                    'url' => ''
                ];
            }
            $result['data'] = $postsArray;
        }
        unset($result['pagination']);
        return $result;
    }

}
