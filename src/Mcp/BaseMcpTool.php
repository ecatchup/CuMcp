<?php
declare(strict_types=1);

namespace CuMcp\Mcp;

use BaserCore\Utility\BcContainerTrait;

/**
 * MCPツールの基底クラス
 *
 * 共通の戻り値作成メソッドとエラーハンドリングを提供
 */
abstract class BaseMcpTool
{
    use BcContainerTrait;

    /**
     * 成功時の戻り値を作成
     *
     * @param mixed $content 戻り値のコンテンツ
     * @param array $meta 追加のメタデータ（paginationなど）
     * @return array MCP仕様に準拠した成功レスポンス
     */
    protected function createSuccessResponse($content, array $meta = []): array
    {
        $response = [
            'isError' => false,
            'content' => $content
        ];

        return array_merge($response, $meta);
    }

    /**
     * エラー時の戻り値を作成
     *
     * @param string $message エラーメッセージ
     * @param \Exception|null $exception 例外オブジェクト（トレース情報用）
     * @return array MCP仕様に準拠したエラーレスポンス
     */
    protected function createErrorResponse(string $message, ?\Exception $exception = null): array
    {
        $response = [
            'isError' => true,
            'content' => $message
        ];

        if ($exception) {
            $response['trace'] = $exception->getTraceAsString();
        }

        return $response;
    }

    /**
     * try-catchブロックを共通化してエラーハンドリングを実行
     *
     * @param callable $callback 実行する処理
     * @return array MCP仕様に準拠したレスポンス
     */
    protected function executeWithErrorHandling(callable $callback): array
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $this->createErrorResponse($e->getMessage(), $e);
        }
    }
}
