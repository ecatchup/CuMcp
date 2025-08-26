<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BaserCore;

use BaserCore\Utility\BcContainerTrait;
use BcSearchIndex\Service\SearchIndexesService;
use BcSearchIndex\Service\SearchIndexesServiceInterface;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Cake\Routing\Router;
use PhpMcp\Server\ServerBuilder;

/**
 * 検索インデックスツールクラス
 */
class SearchIndexesTool
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
        try {
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
            }
        } catch (\Exception $e) {
            return [
                'isError' => true,
                'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        return $result ?? [];
    }

    public function search(string $query): array
    {
        try {
            $entities = $this->searchIndexesService->getIndex([
                'status' => 'publish',
                'keyword' => $query,
                'site_id' => null
			]);
            $result = [];
            foreach($entities as $entity) {
                $result[] = [
                    'id' => $entity->id,
                    'title' => $entity->title,
                    'text' => $entity->detail,
                    'url' => Router::url($entity->url, true)
                ];
            }
            return ['results' => $result];
        } catch (\Exception $e) {
            return [
                'isError' => true,
                'content' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

}
