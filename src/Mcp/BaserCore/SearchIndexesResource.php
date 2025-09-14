<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BaserCore;

use BaserCore\Utility\BcContainerTrait;
use BcSearchIndex\Service\SearchIndexesService;
use BcSearchIndex\Service\SearchIndexesServiceInterface;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Cake\Routing\Router;
use Cake\Utility\Text;
use CuMcp\Mcp\BaseMcpTool;
use CuMcp\Schema\Content\ResourceLinkContent;
use PhpMcp\Server\ServerBuilder;
use PhpMcp\Schema\Content\TextContent;
use PhpMcp\Schema\Content\EmbeddedResource;
use PhpMcp\Schema\Content\TextResourceContents;

/**
 * 検索インデックスリソースクラス
 * MCPプロトコルのリソース機能として検索とデータ取得を提供
 */
class SearchIndexesResource extends BaseMcpTool
{
    use LogTrait;
    use BcContainerTrait;

    /**
     * SearchIndexesService
     * @var SearchIndexesService|SearchIndexesServiceInterface
     */
    private SearchIndexesService|SearchIndexesServiceInterface $searchIndexesService;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->searchIndexesService = $this->getService(SearchIndexesServiceInterface::class);
        Configure::write('App.fullBaseUrl', preg_replace('/\/$/', '', env('SITE_URL', 'https://localhost/')));
    }

    /**
     * ドメイン名を取得してURIスキーム用の識別子として使用
     */
    private function getDomainIdentifier(): string
    {
        $siteUrl = env('SITE_URL', 'https://localhost/');
        $parsedUrl = parse_url($siteUrl);
        $host = $parsedUrl['host'] ?? 'localhost';

        // ドメイン名からピリオドを除去してスキーム識別子として使用
        return str_replace('.', '-', $host);
    }

    /**
     * 検索インデックス用のリソースを ServerBuilder に追加
     */
    public function addResourcesToBuilder(ServerBuilder $builder): ServerBuilder
    {
        $domain = $this->getDomainIdentifier();

        return $builder
            ->withResourceTemplate(
                handler: [self::class, 'search'],
                uriTemplate: "search://{$domain}/search/{query}",
                name: 'search',
                description: 'クエリ文字列でサイトを検索します。',
                mimeType: 'application/json'
            )
            ->withResourceTemplate(
                handler: [self::class, 'fetch'],
                uriTemplate: "search://{$domain}/item/{id}",
                name: 'fetch',
                description: 'IDを指定してデータを取得します。',
                mimeType: 'application/json'
            );
    }    /**
     * IDを指定して検索インデックスのデータを取得
     * @param string $id
     * @return array
     */
    public function fetch(string $id): array
    {
        $domain = $this->getDomainIdentifier();

        try {
            $entity = $this->searchIndexesService->get((int)$id, [
                'status' => 'publish',
                'site_id' => null
            ]);

            if ($entity) {
                $result = [
                    'id' => $entity->id,
                    'title' => $entity->title,
                    'url' => Router::url($entity->url, true),
                    'detail' => $entity->detail,
                    'published' => $entity->published?->format('Y-m-d H:i:s'),
                    'type' => $entity->type,
                    'model' => $entity->model,
                    'model_id' => $entity->model_id,
                    'priority' => $entity->priority
                ];
                return [
                    TextResourceContents::make(
                        uri: "search://{$domain}/item/{$id}",
                        mimeType: 'application/json',
                        text: json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                    )
                ];
            } else {
                return [
                    TextResourceContents::make(
                        uri: "search://{$domain}/item/{$id}",
                        mimeType: 'application/json',
                        text: json_encode(['error' => '指定されたIDの検索インデックスが見つかりません'], JSON_UNESCAPED_UNICODE)
                    )
                ];
            }
        } catch (\Exception $e) {
            return [
                TextResourceContents::make(
                    uri: "search://{$domain}/item/{$id}",
                    mimeType: 'application/json',
                    text: json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE)
                )
            ];
        }
    }

    /**
     * クエリ文字列で検索インデックスを検索
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        $domain = $this->getDomainIdentifier();

        // URLエンコードされている場合はデコードする
        $decodedQuery = urldecode($query);

        try {
            $entities = $this->searchIndexesService->getIndex([
                'status' => 'publish',
                'keyword' => $decodedQuery,
                'site_id' => null,
                'op' => 'or'
            ]);

            $results = [];
            foreach($entities as $entity) {
                $results[] = [
                    'id' => $entity->id,
                    'title' => $entity->title,
                    'url' => Router::url($entity->url, true),
                    'detail' => mb_substr($entity->detail, 0, 200, 'UTF-8'),
                    'published' => $entity->published?->format('Y-m-d H:i:s'),
                    'type' => $entity->type,
                    'model' => $entity->model,
                    'model_id' => $entity->model_id,
                    'priority' => $entity->priority
                ];
            }

            return [
                TextResourceContents::make(
                    uri: "search://{$domain}/search/{$query}",
                    mimeType: 'application/json',
                    text: json_encode([
                        'query' => $decodedQuery, // デコードしたクエリを表示
                        'original_query' => $query, // 元のクエリも保持
                        'count' => count($results),
                        'results' => $results
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                )
            ];
        } catch (\Exception $e) {
            return [
                TextResourceContents::make(
                    uri: "search://{$domain}/search/{$query}",
                    mimeType: 'application/json',
                    text: json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE)
                )
            ];
        }
    }

}
