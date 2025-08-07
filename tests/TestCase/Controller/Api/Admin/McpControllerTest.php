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

namespace CuMcp\Test\TestCase\Controller\Api\Admin;

use BaserCore\TestSuite\BcTestCase;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * MCP HTTP API コントローラーのテスト
 */
class McpControllerTest extends BcTestCase
{
    use IntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin($this->getRequest());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * サーバー情報取得のテスト
     */
    public function testServerInfo()
    {
        $this->get('/baser/api/admin/cu-mcp/mcp/server-info.json');
        $this->assertResponseOk();

        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('server', $result);
        $this->assertEquals('baserCMS MCP Server', $result['server']['name']);
        $this->assertArrayHasKey('available_tools', $result);
    }

    /**
     * ツール情報取得のテスト
     */
    public function testToolInfo()
    {
        $this->get('/baser/api/admin/cu-mcp/mcp/tool-info.json');
        $this->assertResponseOk();

        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('tool', $result);
        $this->assertEquals('addBlogPost', $result['tool']['name']);
        $this->assertArrayHasKey('parameters', $result['tool']);
    }

    /**
     * ブログ記事追加のテスト（正常系）
     */
    public function testAddBlogPostSuccess()
    {
        $data = [
            'title' => 'テスト記事',
            'detail' => 'テスト記事の詳細内容です。'
        ];

        $this->post('/baser/api/admin/cu-mcp/mcp/add-blog-post.json', $data);
        $this->assertResponseOk();

        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('blogPost', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    /**
     * ブログ記事追加のテスト（バリデーションエラー）
     */
    public function testAddBlogPostValidationError()
    {
        $data = [
            'title' => '', // 空のタイトル
            'detail' => 'テスト記事の詳細内容です。'
        ];

        $this->post('/baser/api/admin/cu-mcp/mcp/add-blog-post.json', $data);
        $this->assertResponseCode(400);
    }

    /**
     * ブログ記事追加のテスト（必須項目不足）
     */
    public function testAddBlogPostMissingRequired()
    {
        $data = [
            'title' => 'テスト記事'
            // detail が不足
        ];

        $this->post('/baser/api/admin/cu-mcp/mcp/add-blog-post.json', $data);
        $this->assertResponseCode(400);
    }
}
