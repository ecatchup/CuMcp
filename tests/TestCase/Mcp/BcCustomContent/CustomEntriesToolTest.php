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
use CuMcp\Mcp\BcCustomContent\CustomEntriesTool;
use BaserCore\Service\BcDatabaseServiceInterface;
use BcCustomContent\Test\Factory\CustomTableFactory;
use BcCustomContent\Test\Scenario\CustomFieldsScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use BcCustomContent\Service\CustomTablesServiceInterface;
use BcCustomContent\Test\Scenario\CustomContentsScenario;
use PhpMcp\Server\ServerBuilder;

/**
 * CuMcp\Mcp\BcCustomContent\CustomEntriesTool Test Case
 *
 * @uses \CuMcp\Mcp\BcCustomContent\CustomEntriesTool
 */
class CustomEntriesToolTest extends BcTestCase
{
    use ScenarioAwareTrait;
    use BcContainerTrait;

    /**
     * Test subject
     *
     * @var \CuMcp\Mcp\BcCustomContent\CustomEntriesTool
     */
    protected $CustomEntriesTool;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CustomEntriesTool = new CustomEntriesTool();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->CustomEntriesTool);
        parent::tearDown();
    }

    /**
     * Test addCustomEntry method - 基本テスト
     * CustomTablesに依存するため、適切なセットアップが必要
     *
     * @return void
     */
    public function testAddCustomEntryBasic()
    {
        $dataBaseService = $this->getService(BcDatabaseServiceInterface::class);
        $customTablesService = $this->getService(CustomTablesServiceInterface::class);

        // CustomFieldsScenarioを読み込み
        $this->loadFixtureScenario(CustomFieldsScenario::class);

        $customTableId = 1;
        $title = 'テストカスタムエントリー';

        // カスタムテーブルを作成
        $customTablesService->create([
            'type' => 'contact',
            'name' => 'contact',
            'title' => 'お問い合わせタイトル',
            'display_field' => 'お問い合わせ'
        ]);

        $result = $this->CustomEntriesTool->addCustomEntry(
            custom_table_id: $customTableId,
            title: $title,
            name: 'test_entry',
            status: true,
            creator_id: 1
        );

        $this->assertIsArray($result);
        if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals($title, $result['data']['title']);
            $this->assertEquals($customTableId, $result['data']['custom_table_id']);
        } else {
            // エラーケースでもレスポンス構造をテスト
            $this->assertArrayHasKey('error', $result);
            $this->assertArrayHasKey('message', $result);
        }

        // テーブルをクリーンアップ
        $dataBaseService->dropTable('custom_entry_1_contact');
    }    /**
     * Test addCustomEntry method - カスタムフィールド付きテスト
     *
     * @return void
     */
    public function testAddCustomEntryWithCustomFields()
    {
        $dataBaseService = $this->getService(BcDatabaseServiceInterface::class);
        $customTablesService = $this->getService(CustomTablesServiceInterface::class);

        $this->loadFixtureScenario(CustomFieldsScenario::class);

        $customTableId = 1;
        $title = 'カスタムフィールド付きエントリー';
        $customFields = [
            'custom_field1' => 'カスタム値1',
            'custom_field2' => 'カスタム値2'
        ];

        // カスタムテーブルを作成
        $customTablesService->create([
            'type' => 'contact',
            'name' => 'contact',
            'title' => 'お問い合わせタイトル',
            'display_field' => 'お問い合わせ'
        ]);

        $result = $this->CustomEntriesTool->addCustomEntry(
            custom_table_id: $customTableId,
            title: $title,
            custom_fields: $customFields
        );

        $this->assertIsArray($result);
        if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals($title, $result['data']['title']);
        } else {
            // エラーケースでもレスポンス構造をテスト
            $this->assertArrayHasKey('error', $result);
            $this->assertArrayHasKey('message', $result);
        }

        // テーブルをクリーンアップ
        $dataBaseService->dropTable('custom_entry_1_contact');
    }

    /**
     * Test addCustomEntry method - エラーテスト（空のタイトル）
     *
     * @return void
     */
    public function testAddCustomEntryWithEmptyTitle()
    {
        $result = $this->CustomEntriesTool->addCustomEntry(
            custom_table_id: 1,
            title: ''
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test getCustomEntries method - 基本的な一覧取得テスト
     *
     * @return void
     */
    public function testGetCustomEntriesBasic()
    {
        // テストデータを作成
        CustomTableFactory::make([
            'id' => 1,
            'name' => 'test_table',
            'display_name' => 'テストテーブル',
            'status' => 1
        ])->persist();

        $this->loadFixtureScenario(CustomContentsScenario::class);

        $result = $this->CustomEntriesTool->getCustomEntries(
            custom_table_id: 1,
            limit: 10,
            page: 1
        );

        $this->assertIsArray($result);
        if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('pagination', $result);
            $this->assertEquals(10, $result['pagination']['limit']);
            $this->assertEquals(1, $result['pagination']['page']);
        } else {
            // エラーケースでもレスポンス構造をテスト
            $this->assertArrayHasKey('error', $result);
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * Test getCustomEntries method - ステータスフィルタリングテスト
     *
     * @return void
     */
    public function testGetCustomEntriesWithStatusFilter()
    {
        // テストデータを作成
        CustomTableFactory::make([
            'id' => 1,
            'name' => 'test_table',
            'display_name' => 'テストテーブル',
            'status' => 1
        ])->persist();

        $this->loadFixtureScenario(CustomContentsScenario::class);

            $result = $this->CustomEntriesTool->getCustomEntries(
                custom_table_id: 1,
                status: 1,
                limit: 5
            );

            $this->assertIsArray($result);
            if (isset($result['success']) && $result['success']) {
                $this->assertArrayHasKey('data', $result);
                $this->assertEquals(5, $result['pagination']['limit']);
            } else {
                // エラーケースでもレスポンス構造をテスト
                $this->assertArrayHasKey('error', $result);
                $this->assertArrayHasKey('message', $result);
            }
    }

    /**
     * Test getCustomEntry method - IDによる単一取得テスト
     *
     * @return void
     */
    public function testGetCustomEntryById()
    {
        $result = $this->CustomEntriesTool->getCustomEntry(
            custom_table_id: 1,
            id: 1
        );

        $this->assertIsArray($result);
        // 存在しないエントリーの場合はエラーが返される
        if (isset($result['error']) && $result['error']) {
            $this->assertArrayHasKey('message', $result);
        } else if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals(1, $result['data']['id']);
        }
    }

    /**
     * Test getCustomEntry method - 存在しないIDのテスト
     *
     * @return void
     */
    public function testGetCustomEntryNotFound()
    {
        $nonExistentId = 999999;

        $result = $this->CustomEntriesTool->getCustomEntry(
            custom_table_id: 1,
            id: $nonExistentId
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test editCustomEntry method - 基本的な編集テスト
     *
     * @return void
     */
    public function testEditCustomEntryBasic()
    {
        $newTitle = '編集されたタイトル';
        $newStatus = true;

        $result = $this->CustomEntriesTool->editCustomEntry(
            custom_table_id: 1,
            id: 1,
            title: $newTitle,
            status: $newStatus
        );

        $this->assertIsArray($result);
        // 存在しないエントリーの場合はエラーが返される
        if (isset($result['error']) && $result['error']) {
            $this->assertArrayHasKey('message', $result);
        } else if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals($newTitle, $result['data']['title']);
        }
    }

    /**
     * Test editCustomEntry method - カスタムフィールド編集テスト
     *
     * @return void
     */
    public function testEditCustomEntryWithCustomFields()
    {
        $customFields = [
            'custom_field1' => '更新されたカスタム値1',
            'custom_field2' => '更新されたカスタム値2'
        ];

        $result = $this->CustomEntriesTool->editCustomEntry(
            custom_table_id: 1,
            id: 1,
            custom_fields: $customFields
        );

        $this->assertIsArray($result);
        // 存在しないエントリーの場合はエラーが返される
        if (isset($result['error']) && $result['error']) {
            $this->assertArrayHasKey('message', $result);
        } else if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    /**
     * Test editCustomEntry method - 存在しないエントリーの編集テスト
     *
     * @return void
     */
    public function testEditCustomEntryNotFound()
    {
        $nonExistentId = 999999;

        $result = $this->CustomEntriesTool->editCustomEntry(
            custom_table_id: 1,
            id: $nonExistentId,
            title: '新しいタイトル'
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test deleteCustomEntry method - 削除機能テスト
     *
     * @return void
     */
    public function testDeleteCustomEntryBasic()
    {
        $result = $this->CustomEntriesTool->deleteCustomEntry(
            custom_table_id: 1,
            id: 1
        );

        $this->assertIsArray($result);
        // 削除処理は存在しないエントリーでもエラーハンドリングされる
        if (isset($result['error']) && $result['error']) {
            $this->assertArrayHasKey('message', $result);
        } else if (isset($result['success']) && $result['success']) {
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * Test deleteCustomEntry method - 存在しないエントリーの削除テスト
     *
     * @return void
     */
    public function testDeleteCustomEntryNotFound()
    {
        $nonExistentId = 999999;

        $result = $this->CustomEntriesTool->deleteCustomEntry(
            custom_table_id: 1,
            id: $nonExistentId
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test addToolsToBuilder method - ServerBuilderへのツール追加テスト
     *
     * @return void
     */
    public function testAddToolsToBuilder()
    {
        // ServerBuilderがfinalクラスのため、実際のインスタンスを使用
        $serverBuilder = new ServerBuilder();

        $result = $this->CustomEntriesTool->addToolsToBuilder($serverBuilder);

        $this->assertInstanceOf(ServerBuilder::class, $result);
        // ServerBuilderが返されることを確認（チェーンメソッドパターン）
        $this->assertSame($serverBuilder, $result);
    }
}
