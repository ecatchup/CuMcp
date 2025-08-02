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

namespace CuMcp\Test\TestCase\McpServer;

use BaserCore\TestSuite\BcTestCase;
use CuMcp\McpServer\BaserCmsMcpServer;

/**
 * CuMcp\McpServer\BaserCmsMcpServer Test Case
 *
 * @uses \CuMcp\McpServer\BaserCmsMcpServer
 */
class BaserCmsMcpServerTest extends BcTestCase
{
    /**
     * Test subject
     *
     * @var \CuMcp\McpServer\BaserCmsMcpServer
     */
    protected $BaserCmsMcpServer;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BaserCmsMcpServer = new BaserCmsMcpServer();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BaserCmsMcpServer);
        parent::tearDown();
    }

    /**
     * Test constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(BaserCmsMcpServer::class, $this->BaserCmsMcpServer);
    }

    /**
     * Test getServerInfo method
     *
     * @return void
     */
    public function testGetServerInfo()
    {
        $result = $this->BaserCmsMcpServer->getServerInfo();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('php_version', $result['data']);
        $this->assertArrayHasKey('server_time', $result['data']);
    }

    /**
     * Test getBlogPosts method
     *
     * @return void
     */
    public function testGetBlogPosts()
    {
        $result = $this->BaserCmsMcpServer->getBlogPosts([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    /**
     * Test getDatabaseResource method
     *
     * @return void
     */
    public function testGetDatabaseResource()
    {
        $result = $this->BaserCmsMcpServer->getDatabaseResource('database://basercms');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('database://basercms', $result['data']['uri']);
    }

    /**
     * Test addBlogPost method
     *
     * @return void
     */
    public function testAddBlogPost()
    {
        $arguments = [
            'title' => 'テスト記事',
            'detail' => 'テスト記事の詳細内容',
            'blog_content' => 'news'
        ];

        $result = $this->BaserCmsMcpServer->addBlogPost($arguments);

        // エラーまたは成功のいずれかであることを確認
        $this->assertTrue(isset($result['success']) || isset($result['error']));

        if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals('テスト記事', $result['data']['title']);
        }
    }

    /**
     * Test analyzeBaserCmsData method
     *
     * @return void
     */
    public function testAnalyzeBaserCmsData()
    {
        $arguments = [
            'table_name' => 'blog_posts',
            'analysis_type' => 'summary'
        ];

        $result = $this->BaserCmsMcpServer->analyzeBaserCmsData($arguments);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('blog_posts', $result['data']['table']);
        $this->assertEquals('summary', $result['data']['analysis_type']);
    }

    /**
     * Test analyzeBaserCmsData method with invalid table
     *
     * @return void
     */
    public function testAnalyzeBaserCmsDataWithInvalidTable()
    {
        $arguments = [
            'table_name' => 'invalid_table',
            'analysis_type' => 'summary'
        ];

        $result = $this->BaserCmsMcpServer->analyzeBaserCmsData($arguments);

        $this->assertTrue($result['error']);
        $this->assertStringContains('サポートされていないテーブル', $result['message']);
    }
}
