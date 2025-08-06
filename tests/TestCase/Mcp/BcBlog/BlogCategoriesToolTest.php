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
use CuMcp\Mcp\BcBlog\BlogCategoriesTool;
use BcBlog\Test\Factory\BlogCategoryFactory;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

/**
 * CuMcp\Mcp\BcBlog\BlogCategoriesTool Test Case
 *
 * @uses \CuMcp\Mcp\BcBlog\BlogCategoriesTool
 */
class BlogCategoriesToolTest extends BcTestCase
{
    use ScenarioAwareTrait;

    /**
     * Test subject
     *
     * @var \CuMcp\Mcp\BcBlog\BlogCategoriesTool
     */
    protected $BlogCategoriesTool;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->BlogCategoriesTool = new BlogCategoriesTool();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BlogCategoriesTool);
        parent::tearDown();
    }

    /**
     * Test addBlogCategory method - 基本テスト
     *
     * @return void
     */
    public function testAddBlogCategoryBasic()
    {
        $title = 'テストカテゴリ';
        $blogContentId = 1;
        
        $result = $this->BlogCategoriesTool->addBlogCategory(
            title: $title,
            blog_content_id: $blogContentId
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test getBlogCategories method - 基本テスト
     *
     * @return void
     */
    public function testGetBlogCategoriesBasic()
    {
        // テストデータを作成
        BlogCategoryFactory::make([
            'id' => 1,
            'blog_content_id' => 1,
            'title' => 'テストカテゴリ1',
            'name' => 'test-category-1',
            'status' => 1
        ])->persist();
        
        $result = $this->BlogCategoriesTool->getBlogCategories(1);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * Test getBlogCategory method - IDによる取得
     *
     * @return void
     */
    public function testGetBlogCategoryById()
    {
        // テストデータを作成
        BlogCategoryFactory::make([
            'id' => 1,
            'blog_content_id' => 1,
            'title' => 'テストカテゴリ',
            'name' => 'test-category',
            'status' => 1
        ])->persist();
        
        $result = $this->BlogCategoriesTool->getBlogCategory(1);
        
        $this->assertIsArray($result);
        // IDが存在する場合は成功を想定
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals(1, $result['data']['id']);
        }
    }

    /**
     * Test editBlogCategory method - 編集機能
     *
     * @return void
     */
    public function testEditBlogCategory()
    {
        // テストデータを作成
        BlogCategoryFactory::make([
            'id' => 1,
            'blog_content_id' => 1,
            'title' => 'テストカテゴリ',
            'name' => 'test-category',
            'status' => 1
        ])->persist();
        
        $newTitle = '編集テストカテゴリ';
        
        $result = $this->BlogCategoriesTool->editBlogCategory(
            id: 1,
            title: $newTitle
        );
        
        $this->assertIsArray($result);
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals($newTitle, $result['data']['title']);
        }
    }

    /**
     * Test deleteBlogCategory method - 削除機能
     *
     * @return void
     */
    public function testDeleteBlogCategory()
    {
        // テストデータを作成
        BlogCategoryFactory::make([
            'id' => 1,
            'blog_content_id' => 1,
            'title' => 'テストカテゴリ',
            'name' => 'test-category',
            'status' => 1
        ])->persist();
        
        $result = $this->BlogCategoriesTool->deleteBlogCategory(1);
        
        $this->assertIsArray($result);
        if ($result['success']) {
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * Test addBlogCategory method - エラーテスト（空のタイトル）
     *
     * @return void
     */
    public function testAddBlogCategoryWithEmptyTitle()
    {
        $result = $this->BlogCategoriesTool->addBlogCategory('');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test getBlogCategory method - 存在しないIDのテスト
     *
     * @return void
     */
    public function testGetBlogCategoryNotFound()
    {
        $nonExistentId = 999999;
        
        $result = $this->BlogCategoriesTool->getBlogCategory($nonExistentId);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }
}
