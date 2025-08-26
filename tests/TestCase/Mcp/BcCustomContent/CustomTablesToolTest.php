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

use BaserCore\Service\BcDatabaseServiceInterface;
use BaserCore\TestSuite\BcTestCase;
use BcCustomContent\Service\CustomEntriesServiceInterface;
use BcCustomContent\Service\CustomTablesService;
use BcCustomContent\Service\CustomTablesServiceInterface;
use BcCustomContent\Test\Factory\CustomFieldFactory;
use BcCustomContent\Test\Scenario\CustomTablesScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use CuMcp\Mcp\BcCustomContent\CustomTablesTool;

/**
 * CustomTablesToolTest
 */
class CustomTablesToolTest extends BcTestCase
{

    use ScenarioAwareTrait;

    /**
     * @var CustomTablesTool
     */
    public $CustomTablesTool;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CustomTablesTool = new CustomTablesTool();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        unset($this->CustomTablesTool);
        parent::tearDown();
    }

    /**
     * test addCustomTable
     */
    public function testAddCustomTable()
    {
        CustomFieldFactory::make([
            'name' => 'field1'
        ])->persist();
        CustomFieldFactory::make([
            'name' => 'field2'
        ])->persist();
        $result = $this->CustomTablesTool->addCustomTable(
            'test_table',
            'テストテーブル',
            ['field1', 'field2']
        );

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('test_table', $result['content']['name']);
        $this->assertEquals('テストテーブル', $result['content']['title']);
    }

    /**
     * test getCustomTables
     */
    public function testGetCustomTables()
    {
        $this->loadFixtureScenario(CustomTablesScenario::class);
        $result = $this->CustomTablesTool->getCustomTables();

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['content']);
    }

    /**
     * test getCustomTable
     */
    public function testGetCustomTable()
    {
        $this->loadFixtureScenario(CustomTablesScenario::class);
        $result = $this->CustomTablesTool->getCustomTable(2);

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals(2, $result['content']['id']);
    }

    /**
     * test editCustomTable
     */
    public function testEditCustomTable()
    {
        $this->loadFixtureScenario(CustomTablesScenario::class);
        $result = $this->CustomTablesTool->editCustomTable(
            2,
            'updated_table',
            '更新されたテーブル',
            'default',
            'name',
            0,
            ['field3', 'field4']
        );

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('updated_table', $result['content']['name']);
        $this->assertEquals('更新されたテーブル', $result['content']['title']);
    }

    /**
     * test deleteCustomTable
     */
    public function testDeleteCustomTable()
    {
        $customEntriesService = $this->getService(CustomEntriesServiceInterface::class);
        $customTablesService = $this->getService(CustomTablesServiceInterface::class);
        $customTablesService->create([
            'name' => 'test_table',
            'title' => 'テストテーブル',
            'type' => 'default'
        ]);
        $customEntriesService->setup(1);
        $result = $this->CustomTablesTool->deleteCustomTable(1);

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('カスタムテーブルを削除しました', $result['content']);
    }

    /**
     * test getCustomTables with search parameters
     */
    public function testGetCustomTablesWithSearch()
    {
        $this->loadFixtureScenario(CustomTablesScenario::class);
        $result = $this->CustomTablesTool->getCustomTables('test', 1, 'default', 10, 1);

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['page']);
        $this->assertEquals(10, $result['pagination']['limit']);
    }

    /**
     * test getCustomTable with invalid ID
     */
    public function testGetCustomTableWithInvalidId()
    {
        $result = $this->CustomTablesTool->getCustomTable(999);

        $this->assertTrue($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('Record not found in table `custom_tables`.', $result['content']);
    }

    /**
     * test editCustomTable with invalid ID
     */
    public function testEditCustomTableWithInvalidId()
    {
        $result = $this->CustomTablesTool->editCustomTable(999, 'test', 'Test Table');

        $this->assertTrue($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('Record not found in table `custom_tables`.', $result['content']);
    }

    /**
     * test deleteCustomTable with invalid ID
     */
    public function testDeleteCustomTableWithInvalidId()
    {
        $result = $this->CustomTablesTool->deleteCustomTable(999);

        $this->assertTrue($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('Record not found in table `custom_tables`.', $result['content']);
    }

    /**
     * test addCustomTable without customFieldNames
     */
    public function testAddCustomTableWithoutCustomFieldNames()
    {
        $result = $this->CustomTablesTool->addCustomTable(
            'simple_table',
            'シンプルテーブル'
        );

        $this->assertArrayHasKey('isError', $result);
        $this->assertFalse($result['isError']);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('simple_table', $result['content']['name']);
        $this->assertEquals('シンプルテーブル', $result['content']['title']);
    }
}
