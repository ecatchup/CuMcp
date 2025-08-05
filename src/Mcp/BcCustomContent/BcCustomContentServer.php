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

namespace CuMcp\Mcp;

use CuMcp\Mcp\BcCustomContent\CustomContentsTool;
use CuMcp\Mcp\CustomEntriesTool;
use CuMcp\Mcp\CustomTablesTool;
use CuMcp\Mcp\CustomFieldsTool;
use CuMcp\Mcp\CustomLinksTool;

/**
 * カスタムコンテンツ機能用MCPサーバー
 *
 * カスタムコンテンツ関連の全てのツールクラス名を提供
 */
class BcCustomContentServer
{
    /**
     * 利用可能なカスタムコンテンツツールクラス名の配列を返却
     *
     * @return array<string> ツールクラス名の配列
     */
    public static function getToolClasses(): array
    {
        return [
            CustomEntriesTool::class,
            CustomTablesTool::class,
            CustomFieldsTool::class,
            CustomContentsTool::class,
            CustomLinksTool::class,
        ];
    }
}
