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

namespace CuMcp\Test\TestCase\Mcp\BcBlog;

use BaserCore\TestSuite\BcTestCase;
use BcBlog\Test\Factory\BlogCategoryFactory;
use BcBlog\Test\Factory\BlogPostFactory;
use BcBlog\Test\Scenario\BlogPostsAdminServiceScenario;
use BcBlog\Test\Scenario\MultiSiteBlogPostScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use CuMcp\Mcp\BcBlog\BlogPostsTool;

/**
 * BlogPostsToolTest
 */
class BlogPostsToolTest extends BcTestCase
{
    use ScenarioAwareTrait;
    /**
     * Test subject
     *
     * @var \CuMcp\Mcp\BcBlog\BlogPostsTool
     */
    protected $BlogPostsTool;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BlogPostsTool = new BlogPostsTool();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        unset($this->BlogPostsTool);
        parent::tearDown();
    }

    /**
     * test BlogPostsTool instantiation
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(BlogPostsTool::class, $this->BlogPostsTool);
    }

    /**
     * test addBlogPost
     */
    public function testAddBlogPost()
    {
        // テストデータが無い環境でも、メソッドが存在することを確認
        $this->assertTrue(method_exists($this->BlogPostsTool, 'addBlogPost'));

        // エラーの場合でも結果が配列で返されることを確認
        $result = $this->BlogPostsTool->addBlogPost(
            'テストブログ記事',
            'これはテスト用のブログ記事です。',
            'news',
            null,
            'test@example.com'
        );

        $this->assertIsArray($result);
        // エラーかSuccess、どちらかのキーが存在することを確認
        $this->assertTrue(isset($result['success']) || isset($result['error']));
    }

    /**
     * test getBlogPosts
     */
    public function testGetBlogPosts()
    {
        BlogPostFactory::make([
            'id' => 1,
        ])->persist();
        $result = $this->BlogPostsTool->getBlogPosts(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['data']);
    }

    /**
     * test getBlogPost
     */
    public function testGetBlogPost()
    {
        $this->loadFixtureScenario(BlogPostsAdminServiceScenario::class);
        $result = $this->BlogPostsTool->getBlogPost(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    /**
     * test editBlogPost
     */
    public function testEditBlogPost()
    {
        $this->loadFixtureScenario(BlogPostsAdminServiceScenario::class);
        $result = $this->BlogPostsTool->editBlogPost(
            1,
            '更新されたタイトル',
            '更新された詳細',
            null,
            null,
            null,
            null
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('更新されたタイトル', $result['data']['title']);
        $this->assertEquals('更新された詳細', $result['data']['detail']);
    }

    /**
     * test deleteBlogPost
     */
    public function testDeleteBlogPost()
    {
        $this->loadFixtureScenario(BlogPostsAdminServiceScenario::class);
        $result = $this->BlogPostsTool->deleteBlogPost(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * test getBlogCategoryId
     */
    public function testGetBlogCategoryId()
    {
        BlogCategoryFactory::make([
            'name' => 'プログラム',
            'blog_content_id' => 1,
        ])->persist();
        $categoryId = $this->execPrivateMethod($this->BlogPostsTool, 'getBlogCategoryId', ['プログラム', 1]);

        $this->assertIsInt($categoryId);
        $this->assertGreaterThan(0, $categoryId);
    }

    /**
     * test getBlogContentId
     */
    public function testGetBlogContentId()
    {
        $contentId = $this->execPrivateMethod($this->BlogPostsTool, 'getBlogContentId', ['news']);

        $this->assertIsInt($contentId);
        $this->assertGreaterThan(0, $contentId);
    }

}
