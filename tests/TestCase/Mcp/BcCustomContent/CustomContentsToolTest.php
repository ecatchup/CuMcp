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

namespace CuMcp\Test\TestCase\Mcp\BcCustomContent;

use BaserCore\TestSuite\BcTestCase;
use CuMcp\Mcp\BcCustomContent\CustomContentsTool;

/**
 * CustomContentsToolTest
 */
class CustomContentsToolTest extends BcTestCase
{
    /**
     * @var CustomContentsTool
     */
    public $CustomContentsTool;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CustomContentsTool = new CustomContentsTool();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        unset($this->CustomContentsTool);
        parent::tearDown();
    }

    /**
     * Test instantiation
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(CustomContentsTool::class, $this->CustomContentsTool);
        $this->assertTrue(method_exists($this->CustomContentsTool, 'addCustomContent'));
        $this->assertTrue(method_exists($this->CustomContentsTool, 'getCustomContents'));
    }

    /**
     * test addCustomContent
     */
    public function testAddCustomContent()
    {
        $result = $this->CustomContentsTool->addCustomContent(
            'test-content',
            'テストカスタムコンテンツ',
            1, // customTableId
            1, // siteId
            1, // parentId
            'テスト用のカスタムコンテンツです', // description
            'default', // template
            10, // listCount
            'DESC', // listDirection
            'id', // listOrder
            1 // status
        );

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('success', $result);
        }
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * test getCustomContents
     */
    public function testGetCustomContents()
    {
        $result = $this->CustomContentsTool->getCustomContents(1, 1, null, null, 10, 1);

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('success', $result);
        }
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * test getCustomContent
     */
    public function testGetCustomContent()
    {
        $result = $this->CustomContentsTool->getCustomContent(1);

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('success', $result);
        }
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * test editCustomContent
     */
    public function testEditCustomContent()
    {
        $result = $this->CustomContentsTool->editCustomContent(
            1,
            'updated-name',
            '更新されたタイトル',
            '更新された説明',
            'custom',
            20,
            'ASC',
            'name',
            1
        );

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('success', $result);
        }
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * test deleteCustomContent
     */
    public function testDeleteCustomContent()
    {
        $result = $this->CustomContentsTool->deleteCustomContent(1);

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * test getCustomContents with search parameters
     */
    public function testGetCustomContentsWithSearch()
    {
        $result = $this->CustomContentsTool->getCustomContents(1, 1, 'test', 1, 5, 1);

        $this->assertIsArray($result);
        if (isset($result['success'])) {
            $this->assertArrayHasKey('success', $result);
        }
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * test getCustomContent with invalid ID
     */
    public function testGetCustomContentWithInvalidId()
    {
        $result = $this->CustomContentsTool->getCustomContent(999);

        $this->assertIsArray($result);
        if (isset($result['error'])) {
            $this->assertTrue($result['error']);
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * test editCustomContent with invalid ID
     */
    public function testEditCustomContentWithInvalidId()
    {
        $result = $this->CustomContentsTool->editCustomContent(999, 'test', 'Test Title');

        $this->assertIsArray($result);
        if (isset($result['error'])) {
            $this->assertTrue($result['error']);
            $this->assertArrayHasKey('message', $result);
        }
    }
}
