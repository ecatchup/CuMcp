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

    private SearchIndexesService|SearchIndexesServiceInterface $searchIndexesService;

    public function __construct()
    {
        $this->searchIndexesService = $this->getService(SearchIndexesServiceInterface::class);
        Configure::write('App.fullBaseUrl', preg_replace('/\/$/', '', env('SITE_URL')));
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

    public function fetch(string $id): array
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            $entity = $this->searchIndexesService->get((int) $id, [
                'status' => 'publish',
                'site_id' => null
            ]);

            if($entity) {
                $result = [
                    'id' => $entity->id,
                    'title' => $entity->title,
                    'text' => $entity->detail,
                    'url' => Router::url($entity->url, true)
                ];
                return $this->createSuccessResponse($result);
            } else {
                return $this->createErrorResponse('指定されたIDの検索インデックスが見つかりません');
            }
        });
    }

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
                    'id' => $entity->id,
                    'title' => $entity->title,
                    'text' => $entity->detail,
                    'url' => Router::url($entity->url, true)
                ];
            }

            return $this->createSuccessResponse([
                'results' => $results,
                'count' => count($results),
                'query' => $query
            ]);
        });
    }

}
