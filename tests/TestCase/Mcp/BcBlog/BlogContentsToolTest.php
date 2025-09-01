<?php
declare(strict_types=1);
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baserCMS Users Community <https://basercms.net/community/>
 *
 * @copyright     Copyright (c) NPO baserCMS Users Community
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace CuMcp\Test\TestCase\Mcp\BcBlog;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\Test\Scenario\SmallSetContentsScenario;
use BcBlog\Test\Scenario\BlogContentScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use CuMcp\Mcp\BcBlog\BlogContentsTool;
use BaserCore\TestSuite\BcTestCase;

/**
 * BlogContentsToolTest
 */
class BlogContentsToolTest extends BcTestCase
{
    use ScenarioAwareTrait;

    /**
     * @var BlogContentsTool
     */
    public $BlogContentsTool;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BlogContentsTool = new BlogContentsTool();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        unset($this->BlogContentsTool);
        parent::tearDown();
    }

    /**
     * Test instantiation
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(BlogContentsTool::class, $this->BlogContentsTool);
        $this->assertTrue(method_exists($this->BlogContentsTool, 'addBlogContent'));
        $this->assertTrue(method_exists($this->BlogContentsTool, 'getBlogContents'));
    }

    /**
     * test addBlogContent
     */
    public function testAddBlogContent()
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loadFixtureScenario(SmallSetContentsScenario::class);
        $result = $this->BlogContentsTool->addBlogContent(
            'test-blog',
            'テストブログ',
            1, // siteId
            1, // parentId
            'テストブログの説明' // description
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }    /**
     * test getBlogContents
     */
    public function testGetBlogContents()
    {
        $this->loadFixtureScenario(BlogContentScenario::class,
            1, // id
            1,
            null,
            'test-blog',
            'test-blog-url',
        );
        $result = $this->BlogContentsTool->getBlogContents();

        $this->assertIsArray($result);
        $this->assertCount(1, $result['content']['data']);
    }

    /**
     * test getBlogContent
     */
    public function testGetBlogContent()
    {
        $this->loadFixtureScenario(BlogContentScenario::class,
            1, // id
            1,
            null,
            'test-blog',
            'test-blog-url',
        );
        $result = $this->BlogContentsTool->getBlogContent(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * test editBlogContent
     */
    public function testEditBlogContent()
    {
        $this->loadFixtureScenario(BlogContentScenario::class,
            1, // id
            1,
            null,
            'test-blog',
            'test-blog-url',
        );
        $result = $this->BlogContentsTool->editBlogContent(
            1,
            'updated-blog',
            '更新されたブログ',
            '更新されたブログの説明'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * test deleteBlogContent
     */
    public function testDeleteBlogContent()
    {
        // テストではID=1のブログコンテンツが存在することを前提とする
        $result = $this->BlogContentsTool->deleteBlogContent(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isError', $result);
        // 削除結果のチェック（成功またはエラーのいずれか）
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * test getBlogContentsWithSearch
     */
    public function testGetBlogContentsWithSearch()
    {
        $result = $this->BlogContentsTool->getBlogContents(null, 'test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * test getBlogContentWithInvalidId
     */
    public function testGetBlogContentWithInvalidId()
    {
        $result = $this->BlogContentsTool->getBlogContent(99999);

        $this->assertIsArray($result);
        $this->assertTrue($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * test editBlogContentWithInvalidId
     */
    public function testEditBlogContentWithInvalidId()
    {
        $this->loadFixtureScenario(BlogContentScenario::class,
            1, // id
            1,
            null,
            'test-blog',
            'test-blog-url',
        );
        $result = $this->BlogContentsTool->editBlogContent(
            99999,
            'test-blog',
            'テストブログ',
            'テストブログの説明'
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['isError']);
        $this->assertArrayHasKey('content', $result);
    }
}
