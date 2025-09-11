<?php
declare(strict_types=1);

namespace CuMcp\Mcp;

use BaserCore\Utility\BcContainerTrait;
use BaserCore\Utility\BcUtil;

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

    /**
     * 値がファイルアップロード可能な形式かどうかを判定
     *
     * @param mixed $value 判定対象の値
     * @return bool ファイルアップロード可能な形式の場合true
     */
    protected function isFileUploadable($value): bool
    {
        if (is_array($value)) {
            return true;
        }

        // Base64データの場合
        if (strpos($value, 'data:') === 0) {
            return true;
        }

        // URLの場合（http/httpsで始まる）
        if (preg_match('/^https?:\/\//', $value)) {
            return true;
        }

        return false;
    }

    /**
     * ファイルアップロード処理
     *
     * @param string $fileData ファイルパス、URL、またはbase64エンコードされたデータ
     * @param string $fieldName フィールド名（ログ用）
     * @return array|false アップロード情報の配列、失敗時はfalse
     */
    protected function processFileUpload(string $fileData, string $fieldName = 'file'): array|false
    {
        try {
            // Base64データの場合
            if (strpos($fileData, 'data:') === 0) {
                return $this->processBase64File($fileData);
            }

            // URLの場合はダウンロードして処理
            if (preg_match('/^https?:\/\//', $fileData)) {
                return $this->processUrlFile($fileData);
            }

            if(!empty($fileData)) {
                return $this->processChunkFile($fileData);
            }

            throw new \Exception('不正なファイルデータ形式です: ' . $fileData);

        } catch (\Exception $e) {
            // エラーログを出力
            if (!BcUtil::isTest()) {
                error_log($fieldName . 'の処理に失敗しました: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * チャンクファイルを処理
     *
     * @param string $fileData チャンクファイル名
     * @return array アップロード情報の配列
     * @throws \Exception
     */
    public function processChunkFile(string $fileData): array
    {
        $filePath = TMP . 'mcp_uploads' . DS . $fileData;
        if(!file_exists($filePath)) {
            throw new \Exception('チャンクファイルが存在しません');
        }

        // ファイル情報を取得
        $fileSize = filesize($filePath);
        $fileName = basename($fileData);

        // ファイル拡張子を取得
        $pathInfo = pathinfo($fileName);
        $extension = strtolower($pathInfo['extension'] ?? '');

        // 許可された拡張子かチェック
        if (!$this->isAllowedExtension($extension)) {
            throw new \Exception('サポートされていないファイル形式です: ' . $extension);
        }

        // MIMEタイプを取得
        $mimeType = $this->getMimeTypeFromExtension($extension);

        // アップロード情報として返す
        return [
            'name' => $fileName,
            'type' => $mimeType,
            'tmp_name' => $filePath,
            'error' => UPLOAD_ERR_OK,
            'size' => $fileSize,
            'ext' => $extension
        ];
    }

    /**
     * Base64エンコードされたファイルデータを処理
     *
     * @param string $base64Data base64エンコードされたファイルデータ
     * @return array アップロード情報の配列
     * @throws \Exception
     */
    protected function processBase64File(string $base64Data): array
    {
        // data:mime/type;base64,... の形式から必要な情報を抽出
        if (!preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
            throw new \Exception('不正なbase64ファイル形式です');
        }

        $mimeType = $matches[1];
        $encodedData = $matches[2];

        // base64として有効かチェック
        if (!preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $encodedData)) {
            throw new \Exception('base64デコードに失敗しました');
        }

        $decodedData = base64_decode($encodedData, true);

        if ($decodedData === false) {
            throw new \Exception('base64デコードに失敗しました');
        }

        // ファイル拡張子を取得
        $extension = $this->getExtensionFromMimeType($mimeType);

        // 一意のファイル名を生成
        $fileName = 'upload_' . uniqid() . '.' . $extension;
        $tmpPath = sys_get_temp_dir() . '/' . $fileName;

        // 一時ファイルに保存
        if (file_put_contents($tmpPath, $decodedData) === false) {
            throw new \Exception('一時ファイルの作成に失敗しました');
        }

        // アップロード情報として返す
        return [
            'name' => $fileName,
            'type' => $mimeType,
            'tmp_name' => $tmpPath,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($decodedData),
            'ext' => $extension
        ];
    }

    /**
     * URLからファイルをダウンロードして処理
     *
     * @param string $url ファイルのURL
     * @return array アップロード情報の配列
     * @throws \Exception
     */
    protected function processUrlFile(string $url): array
    {
        // URLの妥当性チェック
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('不正なURL形式です: ' . $url);
        }

        // HTTPSまたはHTTPのみ許可
        if (!preg_match('/^https?:\/\//', $url)) {
            throw new \Exception('HTTPまたはHTTPSのURLのみサポートされています: ' . $url);
        }

        // ユーザーエージェントを設定してファイルをダウンロード
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: baserCMS-MCP-Client/1.0\r\n",
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 3
            ]
        ]);

        $fileData = @file_get_contents($url, false, $context);

        if ($fileData === false) {
            throw new \Exception('URLからファイルをダウンロードできませんでした: ' . $url);
        }

        // ファイルサイズをチェック（10MBまで）
        $fileSize = strlen($fileData);
        if ($fileSize > 10 * 1024 * 1024) {
            throw new \Exception('ファイルサイズが大きすぎます（10MB以下にしてください）');
        }

        // レスポンスヘッダーからContent-Typeを取得
        $mimeType = 'application/octet-stream';
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'content-type:') === 0) {
                    $mimeType = trim(substr($header, 13));
                    // パラメータを除去（例: "image/jpeg; charset=utf-8" -> "image/jpeg"）
                    if (strpos($mimeType, ';') !== false) {
                        $mimeType = trim(explode(';', $mimeType)[0]);
                    }
                    break;
                }
            }
        }

        // URLから拡張子を推測
        $urlPath = parse_url($url, PHP_URL_PATH);
        $extension = '';
        if ($urlPath) {
            $pathInfo = pathinfo($urlPath);
            $extension = strtolower($pathInfo['extension'] ?? '');
        }

        // MIMEタイプから拡張子を取得（URLから取得できない場合）
        if (empty($extension)) {
            $extension = $this->getExtensionFromMimeType($mimeType);
        }

        // ファイル形式のチェック
        if (!$this->isAllowedExtension($extension)) {
            throw new \Exception('サポートされていないファイル形式です: ' . $extension);
        }

        // 一意のファイル名を生成
        $fileName = 'download_' . uniqid() . '.' . $extension;
        $tmpPath = sys_get_temp_dir() . '/' . $fileName;

        // 一時ファイルに保存
        if (file_put_contents($tmpPath, $fileData) === false) {
            throw new \Exception('一時ファイルの作成に失敗しました');
        }

        return [
            'name' => $fileName,
            'type' => $mimeType,
            'tmp_name' => $tmpPath,
            'error' => UPLOAD_ERR_OK,
            'size' => $fileSize,
            'ext' => $extension
        ];
    }

    /**
     * 拡張子からMIMEタイプを取得
     *
     * @param string $extension ファイル拡張子
     * @return string MIMEタイプ
     */
    protected function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            // 画像
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',

            // ドキュメント
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',

            // アーカイブ
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',

            // 音声・動画
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * MIMEタイプから拡張子を取得
     *
     * @param string $mimeType MIMEタイプ
     * @return string ファイル拡張子
     */
    protected function getExtensionFromMimeType(string $mimeType): string
    {
        $extensions = [
            // 画像
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/x-icon' => 'ico',

            // ドキュメント
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',

            // アーカイブ
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-tar' => 'tar',
            'application/gzip' => 'gz',

            // 音声・動画
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'video/mp4' => 'mp4',
            'video/x-msvideo' => 'avi',
            'video/quicktime' => 'mov',
        ];

        return $extensions[$mimeType] ?? 'bin';
    }

    /**
     * 許可された拡張子かチェック
     *
     * @param string $extension ファイル拡張子
     * @return bool 許可されている場合はtrue
     */
    protected function isAllowedExtension(string $extension): bool
    {
        // デフォルトで許可する拡張子（baserCMSの設定を参考）
        $allowedExtensions = [
            // 画像
            'gif', 'jpg', 'jpeg', 'png', 'webp', 'svg', 'bmp', 'ico',
            // ドキュメント
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            // アーカイブ
            'zip', 'rar', 'tar', 'gz',
            // 音声・動画（必要に応じて有効化）
            // 'mp3', 'wav', 'mp4', 'avi', 'mov'
        ];

        return in_array(strtolower($extension), $allowedExtensions);
    }

    /**
     * 画像ファイル専用のアップロード処理
     *
     * @param string $imageData 画像ファイルパス、URL、またはbase64エンコードされたデータ
     * @return array|false アップロード情報の配列、失敗時はfalse
     */
    protected function processImageUpload(string $imageData): array|false
    {
        $result = $this->processFileUpload($imageData, 'image');

        // 配列の場合は画像ファイルかチェック
        if (is_array($result)) {
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
            if (!in_array($result['ext'], $imageExtensions)) {
                throw new \Exception('画像ファイルではありません: ' . $result['ext']);
            }
        }

        return $result;
    }

    /**
     * 一時ファイルをクリーンアップ
     *
     * @param string $tmpPath 一時ファイルのパス
     */
    protected function cleanupTempFile(string $tmpPath): void
    {
        if (file_exists($tmpPath) && strpos($tmpPath, sys_get_temp_dir()) === 0) {
            unlink($tmpPath);
        }
    }

    /**
     * 配列データからCakePHPのUploadedFileオブジェクトを作成
     *
     * @param array $fileData ファイル情報の配列
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    protected function createUploadedFileFromArray(array $fileData): \Psr\Http\Message\UploadedFileInterface
    {
        // ファイルストリームを作成
        $stream = fopen($fileData['tmp_name'], 'r');

        return new \Laminas\Diactoros\UploadedFile(
            $stream,                  // stream
            $fileData['size'],        // size
            $fileData['error'],       // error
            $fileData['name'],        // clientFilename
            $fileData['type']         // clientMediaType
        );
    }
}
