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

namespace CuMcp\Test\TestCase\Command;

use BaserCore\TestSuite\BcTestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use CuMcp\Command\McpServerCommand;

/**
 * CuMcp\Command\McpServerCommand Test Case
 *
 * @uses \CuMcp\Command\McpServerCommand
 */
class McpServerCommandTest extends BcTestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     */
    public function testBuildOptionParser()
    {
        $command = new McpServerCommand();
        $parser = $command->getOptionParser();

        $options = $parser->getOptions();
        $this->assertArrayHasKey('transport', $options);
        $this->assertArrayHasKey('host', $options);
        $this->assertArrayHasKey('port', $options);
        $this->assertArrayHasKey('config', $options);

        $this->assertEquals('stdio', $options['transport']['default']);
        $this->assertEquals('localhost', $options['host']['default']);
        $this->assertEquals('3000', $options['port']['default']);
    }

    /**
     * Test execute method with invalid transport
     *
     * @return void
     */
    public function testExecuteWithInvalidTransport()
    {
        $this->exec('cu_mcp.server --transport=invalid');
        $this->assertExitError();
        $this->assertErrorContains('サポートされていないトランスポートタイプ');
    }

    /**
     * Test execute method help
     *
     * @return void
     */
    public function testExecuteHelp()
    {
        $this->exec('cu_mcp.server --help');
        $this->assertExitSuccess();
        $this->assertOutputContains('baserCMS MCP サーバーを起動します');
    }
}
