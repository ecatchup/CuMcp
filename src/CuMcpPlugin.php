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

namespace CuMcp;

use BaserCore\BcPlugin;
use Cake\Console\CommandCollection;

/**
 * Plugin for CuMcp
 */
class CuMcpPlugin extends BcPlugin
{

    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        // MCPサーバーコマンドを追加
        $commands->add('cu_mcp.server', \CuMcp\Command\McpServerCommand::class);

        $commands = parent::console($commands);

        return $commands;
    }

}
