<?php

namespace CuMcp\Mcp\BaserCore;

use CuMcp\Mcp\BaseMcpTool;
use PhpMcp\Server\ServerBuilder;

class FileUploadTool extends BaseMcpTool
{
    private $uploadDir = TMP . 'mcp_uploads/';

    public function __construct()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * 検索インデックス用のツールを ServerBuilder に追加
     */
    public function addToolsToBuilder(ServerBuilder $builder): ServerBuilder
    {
        return $builder
            ->withTool(
                handler: [self::class, 'sendFileChunk'],
                name: 'sendFileChunk',
                description: 'ファイルをチャンク分割して送信します。大きなファイルを小さな部分に分けて段階的にアップロードするために使用します。分割したチャンクは30KB以下にしてください。',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'fileId' => ['type' => 'string', 'description' => 'ファイルを一意に識別するID（必須）'],
                        'chunkIndex' => ['type' => 'number', 'description' => '現在のチャンクのインデックス番号（0から開始）（必須）'],
                        'totalChunks' => ['type' => 'number', 'description' => 'ファイル全体のチャンク総数（必須）'],
                        'chunkData' => ['type' => 'string', 'description' => 'base64エンコードされたチャンクデータ（30KB以下）（必須）'],
                        'filename' => ['type' => 'string', 'description' => 'ファイル名（拡張子含む）（必須）'],
                    ],
                    'required' => ['fileId', 'chunkIndex', 'totalChunks', 'chunkData', 'filename']
                ]
            );
    }

    public function sendFileChunk(string $fileId, int $chunkIndex, int $totalChunks, string $chunkData, string $filename): array
    {
        return $this->executeWithErrorHandling(function() use ($fileId, $chunkIndex, $totalChunks, $chunkData, $filename) {
            // チャンクファイルとして保存
            $chunkFile = $this->uploadDir . $fileId . '.part' . $chunkIndex;
            file_put_contents($chunkFile, base64_decode($chunkData));

            // 全チャンク受信完了チェック
            if ($this->allChunksReceived($fileId, $totalChunks)) {
                return $this->createSuccessResponse($this->mergeChunks($fileId, $totalChunks, $filename));
            }
            return $this->createSuccessResponse(['status' => 'chunk_received', 'progress' => $chunkIndex + 1]);
        });
    }

    private function mergeChunks($fileId, $totalChunks, $filename)
    {
        $finalFile = $this->uploadDir . $filename;
        $handle = fopen($finalFile, 'wb');

        for($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $this->uploadDir . $fileId . '.part' . $i;
            if (file_exists($chunkFile)) {
                $chunkData = file_get_contents($chunkFile);
                fwrite($handle, $chunkData);
                unlink($chunkFile); // チャンクファイル削除
            }
        }

        fclose($handle);
        return ['status' => 'complete', 'file' => $finalFile];
    }

    private function allChunksReceived($fileId, $totalChunks) {
        // 方法1: ファイル存在チェックによる確認
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $this->uploadDir . $fileId . '.part' . $i;
            if (!file_exists($chunkFile)) {
                return false; // 欠損チャンクがある
            }
        }
        return true; // 全チャンク揃っている
    }

}
