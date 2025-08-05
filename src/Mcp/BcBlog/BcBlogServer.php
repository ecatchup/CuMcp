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

use CuMcp\Mcp\BcBlog\BlogPostsTool;
use CuMcp\Mcp\BcBlog\BlogCategoriesTool;
use CuMcp\Mcp\BcBlog\BlogTagsTool;
use CuMcp\Mcp\BcBlog\BlogContentsTool;

/**
 * ブログ機能用MCPサーバー
 *
 * ブログ関連の全てのツールクラス名を提供
 */
class BcBlogServer
{
    /**
     * 利用可能なブログツールクラス名の配列を返却
     *
     * @return array<string> ツールクラス名の配列
     */
    public static function getToolClasses(): array
    {
        return [
            BlogPostsTool::class,
            BlogCategoriesTool::class,
            BlogTagsTool::class,
            BlogContentsTool::class,
        ];
    }
}
