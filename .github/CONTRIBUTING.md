# CuMcp への貢献

開発する場合には次の点に注意してください。

## ユニットテスト

プラグイン単体でユニットテストが可能となっています。

## リリース

baserマーケットでの配布版用に、vendorフォルダを含む形でリリースします。  
その際、次のコマンドで、vendor フォルダを生成しなおしてください。

```shell
composer install --no-dev -o
```

## 注意事項
`php-mcp/server` が要求している、`react/http` が、`psr/http-message: ^1.0` を要求しているため、`psr/http-message` の2系が導入されている環境の場合、正常に動作しません。

`php-mcp/server` が今後アップデートされない場合、別のライブラリへの変更を検討する必要があります。

※ baserCMS 5.1.10 では、`psr/http-message: ^1.0` で動作しています。

