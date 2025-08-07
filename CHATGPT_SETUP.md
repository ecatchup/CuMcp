# ChatGPTとbaserCMS CuMcpプラグインの連携設定

このガイドでは、ChatGPTのMCP（Model Context Protocol）機能を使用してbaserCMSのデータを操作する方法を説明します。

## 前提条件

- baserCMS 5.0.7以上
- CuMcpプラグインがインストール・有効化されている
- ChatGPT Plus または Pro アカウント
- ChatGPT Desktop アプリケーション
- **パブリックアクセス可能なbaserCMSサーバー**（重要）

**⚠️ 重要な制限**: ChatGPTはローカルURL（localhost、127.0.0.1）にはアクセスできません。本番環境またはパブリックアクセス可能な開発環境が必要です。

## ChatGPTでのMCP設定

ChatGPTでは**HTTPベースのMCPサーバー**として接続します。従来のSTDIOベースではなく、URLを指定する方式を使用します。

### 1. baserCMS MCPサーバーの起動

まず、baserCMS管理画面からMCPサーバーを起動します：

1. baserCMS管理画面にログイン
2. 「CuMcp」→「MCPサーバー管理」にアクセス
3. 「起動」ボタンをクリックしてMCPサーバーを開始

### 2. ChatGPT Desktop アプリケーションでの設定

ChatGPT Desktop アプリケーションで新しいコネクターを追加：

1. ChatGPT Desktop アプリを開く
2. 設定画面を開く
3. 「新しいコネクター」を追加
4. 以下の設定を入力：

**名前**: `baserCMS`
**説明**: `baserCMSのブログ記事とカスタムコンテンツを管理`
**MCPサーバーのURL**: `https://your-public-domain.com/cu-mcp/mcp-proxy`
**認証**: `OAuth`（必要に応じて）

**重要**: 
- `your-public-domain.com` 部分を実際のパブリックドメインに変更してください
- HTTPSが推奨されます（多くの場合必須）
- ローカル環境（localhost）では動作しません

### 3. baserCMS管理画面でURL確認

baserCMS管理画面で正確なURLを確認：

1. 「CuMcp」→「MCPサーバー管理」→「設定」
2. 「AIエージェント設定用URL」セクションで「ChatGPT用URL」を確認
3. 表示されたURLをChatGPTの設定で使用

### 4. 設定の確認

設定完了後：

1. ChatGPT Desktop アプリケーションを再起動
2. 新しいチャットを開始
3. コネクターリストに「baserCMS」が表示されることを確認
4. 接続テストを実行

## 利用可能な機能

### ブログ関連
- ブログ記事の作成、編集、削除
- ブログ記事一覧の取得
- カテゴリ管理

### カスタムコンテンツ関連
- カスタムエントリーの作成、取得
- カスタムフィールドの操作

### システム情報
- サーバー情報の取得
- baserCMSバージョン情報

## 技術的な仕組み

### HTTPプロキシベースの接続

CuMcpプラグインは以下の仕組みでChatGPTと連携します：

1. **ChatGPT** → HTTP リクエスト → **baserCMS(/cu-mcp/mcp-proxy)**
2. **MCPProxyController** → JSON-RPC変換 → **内部MCPサーバー(SSE)**
3. **内部MCPサーバー** → baserCMS操作 → **レスポンス**
4. **MCPProxyController** → HTTP レスポンス → **ChatGPT**

### セキュリティ

- CORS設定により外部からのアクセスを制御
- 管理画面からのサーバー起動/停止制御
- ログファイルによる操作記録

## 使用例

ChatGPTとの会話例：

```
ユーザー: "baserCMSに新しいブログ記事を作成してください。タイトルは「新商品のご紹介」、内容は「弊社の新商品について詳しくご紹介します」でお願いします。"

ChatGPT: MCPを使用してbaserCMSのブログに記事を作成しました。タイトル「新商品のご紹介」で正常に投稿されました。
```

## トラブルシューティング

### MCPサーバーに接続できない場合

1. **サーバー起動確認**：
   - baserCMS管理画面でMCPサーバーが起動していることを確認
   - サーバー状態が「起動中」になっているか確認

2. **URL確認**：
   - ChatGPTに設定したURLが正しいか確認
   - HTTPSの場合はSSL証明書が有効か確認

3. **ネットワーク確認**：
   - ChatGPTからbaserCMSサーバーにアクセス可能か確認
   - ファイアウォールやプロキシの設定を確認

### ログの確認

MCPサーバーのログファイルでエラーを確認：
```bash
tail -f /path/to/basercms/logs/cu_mcp_server.log
```

### 接続テスト

MCPサーバーが正常に動作しているかテスト：
```bash
curl -X POST "https://your-public-domain.com/cu-mcp/mcp-proxy.json" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list"
  }'
```

### よくある問題

1. **SSL証明書エラー**: HTTPSサイトの場合、有効なSSL証明書が必要
2. **CORS エラー**: 設定ファイルのCORS設定を確認
3. **認証エラー**: 必要に応じてOAuth設定を確認
4. **「ローカルURLは使用できません」エラー**: ChatGPTはlocalhost/127.0.0.1にアクセスできません

### ローカル開発環境での制限

ChatGPTは以下のURLにはアクセスできません：
- `http://localhost/`
- `http://127.0.0.1/`
- `http://192.168.x.x/` （プライベートIP）

**開発環境での解決策**：
1. パブリック開発サーバーを使用
2. 本番環境でテスト

## セキュリティ注意事項

- MCPを通じてbaserCMSのデータに直接アクセスできるため、適切なユーザー権限設定を行ってください
- 本番環境での使用時は、適切なアクセス制御を実装してください
- ログファイルに機密情報が記録されないよう注意してください

## サポート

問題が発生した場合は、以下を確認してください：

1. baserCMSのエラーログ
2. MCPサーバーのログ
3. ChatGPTのコンソールエラー

詳細なサポートについては、baserCMSコミュニティにお問い合わせください。
