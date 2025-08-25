<?php
declare(strict_types=1);

namespace CuMcp\Test\TestCase\Mcp\BcBlog;

use BaserCore\TestSuite\BcTestCase;
use BcSearchIndex\Test\Factory\SearchIndexFactory;
use Cake\Datasource\Exception\RecordNotFoundException;
use CuMcp\Mcp\BaserCore\SearchIndexesTool;

/**
 * SearchIndexesToolTest Test Case
 */
class SearchIndexesToolTest extends BcTestCase
{

    /**
     * Test subject
     */
    protected $searchIndexesTool;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->searchIndexesTool = new SearchIndexesTool();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->searchIndexesTool);
        parent::tearDown();
    }

    public function testFetch()
    {
        // テストデータを作成
        SearchIndexFactory::make([[
            'id' => 1,
            'title' => 'テストタイトル1',
            'detail' => 'テスト詳細1',
            'url' => '/test-url-1',
            'status' => 0,
        ], [
            'id' => 2,
            'title' => 'テストタイトル2',
            'detail' => 'テスト詳細2',
            'url' => '/test-url-2',
            'status' => 1,
        ]])->persist();

        $result = $this->searchIndexesTool->fetch("1");
        $this->assertTrue($result['error']);
        $this->assertEquals("Record not found in table `search_indexes`.", $result['message']);
        $result = $this->searchIndexesTool->fetch("2");
        $this->assertArrayHasKey('id', $result);
    }

    public function testSearch()
    {
        // テストデータを作成
        SearchIndexFactory::make([[
            'id' => 1,
            'title' => 'テストタイトル1',
            'detail' => 'テスト詳細1',
            'url' => '/test-url-1',
            'status' => 0,
        ], [
            'id' => 2,
            'title' => 'テストタイトル2',
            'detail' => 'テスト詳細2',
            'url' => '/test-url-2',
            'status' => 1,
        ]])->persist();

        $result = $this->searchIndexesTool->search("詳細");
        $this->assertCount(1, $result['results']);
    }

}
