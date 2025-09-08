# MCPツール ファイルアップロード機能ガイド

## 概要

BaseMcpTool クラスに実装されたファイルアップロード機能により、以下のエンティティでファイルアップロードが可能になりました：

- **BlogPosts**（ブログ記事のアイキャッチ画像）
- **CustomEntries**（カスタムエントリーのファイルフィールド）

この機能により、Base64エンコードデータ、ローカルファイルパス、URLの3つの方式でファイルをアップロードできます。

## 対応ファイル形式

### 画像ファイル
- `.jpg`, `.jpeg` - JPEG画像
- `.png` - PNG画像  
- `.gif` - GIF画像
- `.webp` - WebP画像
- `.svg` - SVGベクター画像

### ドキュメントファイル  
- `.pdf` - PDFドキュメント
- `.doc`, `.docx` - Microsoft Word文書
- `.xls`, `.xlsx` - Microsoft Excel表計算
- `.ppt`, `.pptx` - Microsoft PowerPointプレゼンテーション

### アーカイブファイル
- `.zip` - ZIP圧縮ファイル
- `.tar` - TARアーカイブ
- `.gz` - GZIP圧縮ファイル

### 音声・動画ファイル
- `.mp3` - MP3音声ファイル
- `.wav` - WAVオーディオファイル
- `.mp4` - MP4動画ファイル
- `.avi` - AVI動画ファイル
- `.mov` - QuickTime動画ファイル

## ファイルアップロード方式

### 1. Base64エンコードされたファイル

ファイルをBase64形式でエンコードして送信します。

```json
{
  "eyeCatch": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD..."
}
```

### 2. ローカルファイルパス

サーバー上の既存ファイルパスを指定します。

```json
{
  "eyeCatch": "/path/to/image.jpg"
}
```

### 3. URL（http/https）

Web上のファイルURLを指定します。

```json
{
  "eyeCatch": "https://example.com/image.jpg"
}
```

## BlogPosts での使用例

### addBlogPost - 新規ブログ記事作成

#### Base64データでアイキャッチ画像を追加
```php
$tool = new BlogPostsTool();
$result = $tool->addBlogPost(
    title: "新しい記事",
    detail: "記事の詳細内容です。",
    status: 1, // 公開
    eyeCatch: "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEA..."
);
```

#### ローカルファイルパスでアイキャッチ画像を追加
```php
$tool = new BlogPostsTool();
$result = $tool->addBlogPost(
    title: "ファイルパス記事",
    detail: "ローカルファイルを使用した記事です。",
    status: 1,
    eyeCatch: "/Users/username/Pictures/photo.jpg"
);
```

#### URLでアイキャッチ画像を追加（従来の方法）
```php
$tool = new BlogPostsTool();
$result = $tool->addBlogPost(
    title: "URL記事",
    detail: "Web上の画像を使用した記事です。",
    status: 1,
    eyeCatch: "https://example.com/image.jpg"
);
```

### editBlogPost - ブログ記事編集

#### アイキャッチ画像を更新
```php
$tool = new BlogPostsTool();
$result = $tool->editBlogPost(
    id: 1,
    title: "更新されたタイトル",
    eyeCatch: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA..."
);
```

## CustomEntries での使用例

### addCustomEntry

```php
$tool = new CustomEntriesTool();
$result = $tool->addCustomEntry(
    customTableId: 1,
    title: "新しいエントリー",
    customFields: [
        'image_field' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAE...',
        'document_field' => '/path/to/document.pdf',
        'text_field' => '通常のテキスト値'
    ]
);
```

### editCustomEntry

```php
$tool = new CustomEntriesTool();
$result = $tool->editCustomEntry(
    customTableId: 1,
    id: 1,
    title: "更新されたエントリー",
    customFields: [
        'image_field' => 'https://example.com/new_image.jpg',
        'archive_field' => '/path/to/archive.zip'
    ]
);
```

## エラーハンドリング

ファイルアップロードに失敗した場合、以下のようなエラーが返されます：

```json
{
  "success": false,
  "message": "ファイルアップロードに失敗しました (image_field): 対応していないファイル形式です: .xyz"
}
```

## 技術的詳細

### BaseMcpTool の主要メソッド

#### ファイル処理関連（共通）
1. **`processFileUpload($value)`** - ファイルアップロードの総合処理メソッド
2. **`processBase64File($base64Data)`** - Base64エンコードデータの処理
3. **`processFilePath($filePath)`** - ローカルファイルパスの処理
4. **`isFileUploadable($value)`** - 値がファイルアップロード可能な形式かを判定
5. **`getMimeTypeFromExtension($extension)`** - 拡張子からMIMEタイプを取得

#### BlogPosts専用
6. **`processImageUpload($eyeCatch)`** - 画像専用のアップロード処理（BlogPosts用）

### CustomEntriesTool の専用メソッド

#### カスタムエントリー専用
1. **`getCustomFieldType($customTableId, $fieldName)`** - カスタムフィールドのタイプを取得
2. **`isFileUploadField($customTableId, $fieldName)`** - フィールドがBcCcFileタイプかを判定
3. **`isFileUpload($value, $customTableId, $fieldName)`** - カスタムエントリー用のファイルアップロード判定
4. **`processCustomFields($customFields, $customTableId)`** - カスタムフィールドの一括処理

### メソッド使い分け

#### BlogPostsツール
- **`isFileUploadable()`** を使用してアイキャッチ画像の形式チェック
- フィールドタイプの制約なし（アイキャッチは常に画像ファイル想定）

#### CustomEntriesツール  
- **`isFileUpload()`** を使用してフィールドタイプ + 形式の両方をチェック
- BcCcFileフィールドのみファイルアップロード処理を実行
- カスタムエントリー特有のロジックはCustomEntriesToolに集約

### ファイル保存場所

アップロードされたファイルは baserCMS の標準アップロードディレクトリに保存されます：
- `webroot/files/` 配下の適切なディレクトリ
- ファイル名は重複を避けるため自動的にリネームされます

### セキュリティ対策

#### 1. ファイル形式の検証
- 許可されたファイル形式のみを受け入れ
- 拡張子とMIMEタイプの双方で検証
- 実行可能ファイルは完全に拒否

#### 2. Base64データの検証
- 正規表現でBase64フォーマットを厳密に検証
- データプレフィックス（`data:image/jpeg;base64,`）の確認
- 不正なBase64文字列は即座に拒否

#### 3. ファイルサイズ制限
- baserCMS の既存のファイルアップロード制限を適用
- 設定ファイルでカスタマイズ可能

#### 4. パス検証
- ローカルファイルパスの存在確認
- ディレクトリトラバーサル攻撃の防止
- 読み取り権限の確認

#### 5. 一時ファイル管理
- システムの一時ディレクトリに安全に保存
- 処理完了後の自動クリーンアップ
- 一時ファイル名の衝突回避

## テスト

実装された機能には包括的なユニットテストが含まれています：

### テストファイル一覧
- `BaseMcpToolTest.php` - 基本ファイル処理機能のテスト
- `BlogPostsToolTest.php` - ブログ記事のアイキャッチ画像テスト  
- `CustomEntriesToolTest.php` - カスタムエントリーのファイルフィールドテスト

### テスト内容
- Base64データの処理テスト
- ローカルファイルパスの処理テスト
- URLの処理テスト
- 無効なデータの処理テスト
- MIMEタイプ取得テスト
- エラーハンドリングテスト
- セキュリティ検証テスト

### テスト実行コマンド

```bash
# 全ファイルアップロード関連テストを実行
vendor/bin/phpunit plugins/CuMcp/tests/TestCase/Mcp/

# 個別テスト実行
vendor/bin/phpunit plugins/CuMcp/tests/TestCase/Mcp/BaseMcpToolTest.php
vendor/bin/phpunit plugins/CuMcp/tests/TestCase/Mcp/BcBlog/BlogPostsToolTest.php
vendor/bin/phpunit plugins/CuMcp/tests/TestCase/Mcp/BcCustomContent/CustomEntriesToolTest.php
```

### テスト結果の例
```
PHPUnit 10.5.31 by Sebastian Bergmann and contributors.

..........................                                    26 / 26 (100%)

Time: 00:02.345, Memory: 28.50 MB

OK (26 tests, 78 assertions)
```

## 拡張方法

### 新しいエンティティでファイルアップロード機能を使用する手順

1. **`BaseMcpTool` を継承**
2. **ファイルフィールドを含むメソッドで適切な処理メソッドを呼び出し**
3. **複数ファイルの場合は配列処理を実装**

### 実装例

```php
class NewEntityTool extends BaseMcpTool
{
    public function addNewEntity(string $title, ?string $image = null, ?array $files = null): array
    {
        return $this->executeWithErrorHandling(function() use ($title, $image, $files) {
            $data = ['title' => $title];
            
            // 単一ファイルの処理
            if ($image && $this->isFileUpload($image)) {
                $uploadResult = $this->processImageUpload($image);
                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['data']['path'] ?? $uploadResult['data'];
                } else {
                    throw new InvalidArgumentException("画像アップロードに失敗: " . $uploadResult['message']);
                }
            }
            
            // 複数ファイルの処理
            if (!empty($files)) {
                $data = array_merge($data, $this->processMultipleFiles($files));
            }
            
            // エンティティ保存処理
            // ... 
        });
    }
    
    protected function processMultipleFiles(array $files): array
    {
        $processedFiles = [];
        
        foreach ($files as $fieldName => $value) {
            if ($this->isFileUpload($value)) {
                $uploadResult = $this->processFileUpload($value);
                if ($uploadResult['success']) {
                    $processedFiles[$fieldName] = $uploadResult['data']['path'] ?? $uploadResult['data'];
                } else {
                    throw new InvalidArgumentException("ファイルアップロードに失敗 ({$fieldName}): " . $uploadResult['message']);
                }
            } else {
                $processedFiles[$fieldName] = $value;
            }
        }
        
        return $processedFiles;
    }
}
```

### ベストプラクティス

1. **エラーハンドリング**: 必ず適切な例外処理を実装
2. **ファイル種別チェック**: 用途に応じて適切なファイル形式のみを許可
3. **ログ出力**: アップロード処理の詳細をログに記録
4. **テスト作成**: 新しい機能には必ずユニットテストを作成

## トラブルシューティング

### よくある問題と解決方法

#### 1. Base64デコードエラー
```
エラー: 無効なBase64データです
解決: データプレフィックス（data:image/jpeg;base64,）が正しく含まれているか確認
```

#### 2. ファイル形式エラー  
```
エラー: 対応していないファイル形式です: .xyz
解決: 対応ファイル形式一覧を確認し、適切な形式のファイルを使用
```

#### 3. ファイルパスエラー
```
エラー: ファイルが見つかりません: /path/to/file.jpg
解決: ファイルパスが正しく、ファイルが存在し、読み取り権限があることを確認
```

#### 4. アップロードサイズエラー
```
エラー: ファイルサイズが制限を超えています
解決: baserCMS の設定でファイルサイズ上限を確認・調整
```
