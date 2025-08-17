# CuMcp plugin for baserCMS

baserCMS用のMCP（Model Context Protocol）サーバープラグインです。外部のAIツールやアプリケーションからbaserCMSのデータを操作することができます。

## 機能

- ブログ記事の作成、取得、編集、削除
- ブログカテゴリの管理
- カスタムコンテンツの管理
- カスタムエントリーの作成と取得
- サーバー情報の取得
- STDIO および SSE（HTTP） トランスポートサポート
- HTTPプロキシ経由のMCP通信（ChatGPT対応）

## インストール

### Composerを使用したインストール

```bash
composer require catchup/cu-mcp
```

### 手動インストール

1. このリポジトリをクローンまたはダウンロード
2. `plugins/CuMcp/` ディレクトリに配置
3. 依存関係をインストール：

```bash
cd plugins/CuMcp
composer install
```

## 設定

### プラグインの有効化

baserCMSの管理画面から CuMcp プラグインを有効化してください。

### 設定ファイル

`config/cu_mcp.php` で設定をカスタマイズできます：

```php
return [
    'CuMcp' => [
        'server' => [
            'name' => 'baserCMS MCP Server',
            'version' => '1.0.0'
        ],
        'defaults' => [
            'blog_content_id' => 1,
            'user_id' => 1
        ],
        'logging' => [
            'enabled' => true,
            'level' => 'info'
        ]
    ]
];
```

## 使用方法

### MCPサーバーの起動

#### STDIO モード（推奨）

```bash
# シェルスクリプトを使用
./plugins/CuMcp/bin/start-mcp-server.sh

# または直接cakeコマンドを使用
bin/cake cu_mcp.server --transport=stdio
```

#### SSE モード

```bash
# シェルスクリプトを使用
./plugins/CuMcp/bin/start-mcp-server.sh -t sse -h localhost -p 3000

# または直接cakeコマンドを使用
bin/cake cu_mcp.server --transport=sse --host=127.0.0.1 --port=3000
```

### 利用可能なツール

#### ブログ関連

- `addBlogPost`: ブログ記事を追加
- `getBlogPosts`: ブログ記事一覧を取得
- `editBlogPost`: ブログ記事を編集
- `deleteBlogPost`: ブログ記事を削除

#### カスタムコンテンツ関連

- `addCustomEntry`: カスタムエントリーを追加
- `getCustomEntries`: カスタムエントリー一覧を取得

#### システム情報

- `serverInfo`: サーバー情報を取得

### 使用例

#### ブログ記事の追加

```json
{
  "tool": "addBlogPost",
  "arguments": {
    "title": "新しい記事のタイトル",
    "detail": "記事の詳細内容",
    "category": "お知らせ"
  }
}
```

#### カスタムエントリーの追加

```json
{
  "tool": "addCustomEntry",
  "arguments": {
    "custom_table_id": 1,
    "title": "新しいエントリー",
    "status": true,
    "custom_fields": {
      "field_name": "フィールドの値"
    }
  }
}
```

## クライアント連携

### Claude Desktop

`~/.claude_desktop_config.json` に以下を追加：

```json
{
  "mcpServers": {
    "basercms": {
      "command": "/path/to/basercms/plugins/CuMcp/bin/start-mcp-server.sh",
      "args": [],
      "env": {}
    }
  }
}
```

### ChatGPT Desktop

ChatGPTでの連携については、詳細な設定手順を `CHATGPT_SETUP.md` で確認してください。

ChatGPTは**HTTPベースのURL設定**を使用します：

1. baserCMS管理画面でMCPサーバーを起動
2. ChatGPT Desktopアプリで新しいコネクターを追加
3. MCPサーバーのURL: `https://your-public-domain.com/mcp`

**重要**: 
- ChatGPTではSTDIOスクリプトではなく、HTTP URL方式を使用
- **ローカルURL（localhost）は使用不可** - パブリックアクセス可能なURLが必要
- HTTPSが推奨

### その他のMCPクライアント

STDIOまたはSSEトランスポートをサポートする任意のMCPクライアントで使用できます。

## 開発

### テストの実行

```bash
# ユニットテストの実行
vendor/bin/phpunit

# 特定のテストクラスの実行
vendor/bin/phpunit tests/TestCase/Command/McpServerCommandTest.php
```

### デバッグ

デバッグモードを有効にすると、詳細なログが出力されます：

```bash
# デバッグモードでの起動
DEBUG=1 bin/cake cu_mcp.server --transport=stdio
```

## トラブルシューティング

### よくある問題

1. **MCPサーバーが起動しない**
   - PHP 8.1以上がインストールされているか確認
   - Composerの依存関係がインストールされているか確認
   - ログファイルにエラーメッセージがないか確認

2. **ツールが正常に動作しない**
   - baserCMSのデータベースに接続できているか確認
   - 必要なプラグイン（BcBlog、BcCustomContent）が有効になっているか確認

3. **パーミッションエラー**
   - ログディレクトリの書き込み権限を確認
   - 実行スクリプトの実行権限を確認

### ログの確認

```bash
# MCPサーバーのログを確認
tail -f tmp/logs/mcp_server.log

# baserCMSのログを確認
tail -f logs/error.log
```

## ライセンス

MIT License

## 作者

baserCMS開発チーム

## 貢献

バグレポートや機能要求は、GitHubのIssueで受け付けています。
