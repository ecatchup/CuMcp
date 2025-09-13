<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BaserCore;

use BaserCore\Utility\BcContainerTrait;
use BcSearchIndex\Service\SearchIndexesService;
use BcSearchIndex\Service\SearchIndexesServiceInterface;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Cake\Routing\Router;
use CuMcp\Mcp\BaseMcpTool;
use PhpMcp\Server\ServerBuilder;

/**
 * 検索インデックスツールクラス
 */
class SearchIndexesTool extends BaseMcpTool
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
     * 検索インデックス用のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'search'],
                name: 'search',
                description: 'クエリ文字列でサイトを検索します。',
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

    /**
     * IDを指定して検索インデックスのデータを取得
     * @param string $id
     * @return array
     */
    public function fetch(string $id): array
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            $entity = $this->searchIndexesService->get((int)$id, [
                'status' => 'publish',
                'site_id' => null
            ]);

            if ($entity) {
                $result = [
                    'type' => 'resource',
                    'resource' => [
                        'url' => Router::url($entity->url, true),
                        'text' => $entity->detail,
                        'mineType' => 'text/html',
                    ]
                ];
                return $this->createSuccessResponse($result);
            } else {
                return $this->createErrorResponse('指定されたIDの検索インデックスが見つかりません');
            }
        });
    }

    /**
     * クエリ文字列で検索インデックスを検索
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        return $this->executeWithErrorHandling(function() use ($query) {
            $entities = $this->searchIndexesService->getIndex([
                'status' => 'publish',
                'keyword' => $query,
                'site_id' => null
            ]);

            $results = [];
            foreach($entities as $entity) {
                $results[] = [
                    'type' => 'resource_link',
                    'name' => $entity->id,
                    'uri' => Router::url($entity->url, true),
                    'title' => $entity->title,
                    'description' => $entity->detail,
                ];
            }

            return $this->createSuccessResponse($results, [
                'count' => count($results),
                'query' => $query
            ]);
        });
    }

}
