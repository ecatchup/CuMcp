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
use BcBlog\Test\Scenario\BlogTagsScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use CuMcp\Mcp\BcBlog\BlogTagsTool;

/**
 * BlogTagsToolTest
 */
class BlogTagsToolTest extends BcTestCase
{

    use ScenarioAwareTrait;
    /**
     * @var BlogTagsTool
     */
    public $BlogTagsTool;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BlogTagsTool = new BlogTagsTool();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        unset($this->BlogTagsTool);
        parent::tearDown();
    }

    /**
     * test addBlogTag
     */
    public function testAddBlogTag()
    {

        $result = $this->BlogTagsTool->addBlogTag('テストタグ');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('テストタグ', $result['data']['name']);
    }

    /**
     * test getBlogTags
     */
    public function testGetBlogTags()
    {
        $this->loadFixtureScenario(BlogTagsScenario::class);
        $result = $this->BlogTagsTool->getBlogTags();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['data']);
    }

    /**
     * test getBlogTag
     */
    public function testGetBlogTag()
    {
        $this->loadFixtureScenario(BlogTagsScenario::class);
        $result = $this->BlogTagsTool->getBlogTag(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    /**
     * test editBlogTag
     */
    public function testEditBlogTag()
    {
        $this->loadFixtureScenario(BlogTagsScenario::class);
        $result = $this->BlogTagsTool->editBlogTag(1, '更新されたタグ');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('更新されたタグ', $result['data']['name']);
    }

    /**
     * test deleteBlogTag
     */
    public function testDeleteBlogTag()
    {
        $this->loadFixtureScenario(BlogTagsScenario::class);
        $result = $this->BlogTagsTool->deleteBlogTag(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('ブログタグを削除しました', $result['message']);
    }

    /**
     * test getBlogTags with search parameters
     */
    public function testGetBlogTagsWithSearch()
    {
        $this->loadFixtureScenario(BlogTagsScenario::class);
        $result = $this->BlogTagsTool->getBlogTags('tag1', null, 10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['page']);
        $this->assertEquals(10, $result['pagination']['limit']);
    }

    /**
     * test getBlogTag with invalid ID
     */
    public function testGetBlogTagWithInvalidId()
    {
        $result = $this->BlogTagsTool->getBlogTag(999);

        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Record not found in table `blog_tags`.', $result['message']);
    }

    /**
     * test editBlogTag with invalid ID
     */
    public function testEditBlogTagWithInvalidId()
    {
        $result = $this->BlogTagsTool->editBlogTag(999, 'Test Tag');

        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Record not found in table `blog_tags`.', $result['message']);
    }

    /**
     * test deleteBlogTag with invalid ID
     */
    public function testDeleteBlogTagWithInvalidId()
    {
        $result = $this->BlogTagsTool->deleteBlogTag(999);

        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Record not found in table `blog_tags`.', $result['message']);
    }
}
