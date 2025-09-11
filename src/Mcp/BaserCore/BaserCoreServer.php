<?php
declare(strict_types=1);

namespace CuMcp\Mcp\BaserCore;

/**
 * ブログ機能用MCPサーバー
 *
 * ブログ関連の全てのツールクラス名を提供
 */
class BaserCoreServer
{
    /**
     * 利用可能なブログツールクラス名の配列を返却
     *
     * @return array<string> ツールクラス名の配列
     */
    public static function getToolClasses(): array
    {
        return [
            SearchIndexesTool::class,
            FileUploadTool::class
        ];
    }
}
