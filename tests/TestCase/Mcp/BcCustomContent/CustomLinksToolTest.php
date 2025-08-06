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
use BaserCore\Utility\BcContainerTrait;
use CuMcp\Mcp\BcCustomContent\CustomLinksTool;
use BaserCore\Service\BcDatabaseServiceInterface;
use BcCustomContent\Test\Factory\CustomLinkFactory;
use BcCustomContent\Test\Factory\CustomTableFactory;
use BcCustomContent\Test\Scenario\CustomFieldsScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use BcCustomContent\Service\CustomTablesServiceInterface;
use BcCustomContent\Test\Scenario\CustomContentsScenario;

/**
 * CuMcp\Mcp\BcCustomContent\CustomLinksTool Test Case
 *
 * @uses \CuMcp\Mcp\BcCustomContent\CustomLinksTool
 */
class CustomLinksToolTest extends BcTestCase
{
    use ScenarioAwareTrait;
    use BcContainerTrait;

    /**
     * Test subject
     *
     * @var \CuMcp\Mcp\BcCustomContent\CustomLinksTool
     */
    protected $CustomLinksTool;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CustomLinksTool = new CustomLinksTool();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->CustomLinksTool);
        parent::tearDown();
    }

    /**
     * Test addCustomLink method - 基本テスト
     *
     * @return void
     */
    public function testAddCustomLinkBasic()
    {
        $dataBaseService = $this->getService(BcDatabaseServiceInterface::class);
        $customTablesService = $this->getService(CustomTablesServiceInterface::class);
        $this->loadFixtureScenario(CustomFieldsScenario::class);

        $name = 'test_link_basic';
        $title = 'テストリンク';
        $customTableId = 1;
        $customFieldId = 1;
        $customTablesService->create([
            'type' => 'contact',
            'name' => 'contact',
            'title' => 'お問い合わせタイトル',
            'display_field' => 'お問い合わせ'
        ]);

        $result = $this->CustomLinksTool->addCustomLink(
            name: $name,
            title: $title,
            custom_table_id: $customTableId,
            custom_field_id: $customFieldId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $dataBaseService->dropTable('custom_entry_1_contact');
    }

    /**
     * Test getCustomLink method - IDによる取得
     *
     * @return void
     */
    public function testGetCustomLink()
    {
        // テストデータを作成
        CustomLinkFactory::make([
            'id' => 1,
            'custom_table_id' => 1,
            'custom_field_id' => 1,
            'name' => 'test_link',  // ハイフンをアンダースコアに変更
            'title' => 'テストリンク',
            'status' => 1
        ])->persist();

        $result = $this->CustomLinksTool->getCustomLink(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    /**
     * Test editCustomLink method - 編集機能
     *
     * @return void
     */
    public function testEditCustomLink()
    {
        // テストデータを作成
        CustomLinkFactory::make([
            'id' => 1,
            'custom_table_id' => 1,
            'custom_field_id' => 1,
            'name' => 'test_link',
            'title' => 'テストリンク',
            'status' => 1
        ])->persist();

        $newTitle = '編集テストリンク';

        $result = $this->CustomLinksTool->editCustomLink(
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
     * Test deleteCustomLink method - 削除機能
     *
     * @return void
     */
    public function testDeleteCustomLink()
    {
        $customTablesService = $this->getService(CustomTablesServiceInterface::class);
        $databaseService = $this->getService(BcDatabaseServiceInterface::class);
        // テストデータを作成
        CustomLinkFactory::make([
            'id' => 1,
            'custom_table_id' => 1,
            'custom_field_id' => 1,
            'name' => 'test_link',
            'title' => 'テストリンク',
            'status' => 1
        ])->persist();
        $customTablesService->create([
            'type' => 'contact',
            'name' => 'contact',
            'title' => 'お問い合わせタイトル',
            'display_field' => 'お問い合わせ'
        ]);
        $databaseService->addColumn('custom_entry_1_contact', 'test_link', 'text');
        $result = $this->CustomLinksTool->deleteCustomLink(1);
        $this->assertTrue($result['success']);
        $databaseService->dropTable('custom_entry_1_contact');
    }

    /**
     * Test addCustomLink method - エラーテスト（空の名前）
     *
     * @return void
     */
    public function testAddCustomLinkWithEmptyName()
    {
        $result = $this->CustomLinksTool->addCustomLink(
            name: '',
            title: 'テストタイトル',
            custom_table_id: 1,
            custom_field_id: 1
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test getCustomLink method - 存在しないIDのテスト
     *
     * @return void
     */
    public function testGetCustomLinkNotFound()
    {
        $nonExistentId = 999999;

        $result = $this->CustomLinksTool->getCustomLink($nonExistentId);

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test getCustomLinks method - フィルタリングテスト
     *
     * @return void
     */
    public function testGetCustomLinks()
    {
        CustomTableFactory::make([
            'id' => 1,
            'name' => 'test_table',
            'display_name' => 'テストテーブル',
            'status' => 1
        ])->persist();
        $this->loadFixtureScenario(CustomContentsScenario::class);
        $this->loadFixtureScenario(CustomFieldsScenario::class);

        // ステータス1でフィルタリング
        $result = $this->CustomLinksTool->getCustomLinks(
            custom_table_id: 1,
            status: 1,
            limit: 10
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertCount(2, $result['data']);
        $this->assertArrayHasKey('pagination', $result);
    }
}
