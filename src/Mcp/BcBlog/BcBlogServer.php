<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BcBlog;

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
            BlogContentsTool::class,
            BlogPostsTool::class,
            BlogCategoriesTool::class,
            BlogTagsTool::class,
        ];
    }

    /**
     * 利用可能なリソースクラス名の配列を返却
     *
     * @return array<string> リソースクラス名の配列
     */
    public static function getResourceClasses(): array
    {
        return [
            // 現在はリソースクラスなし
        ];
    }

}
