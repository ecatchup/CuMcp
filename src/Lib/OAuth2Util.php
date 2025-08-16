<?php
namespace CuMcp\Lib;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;

class OAuth2Util
{

    /**
     * CakePHPリクエストをPSR-7リクエストに変換
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function createPsr7Request(\Cake\Http\ServerRequest $request): \Psr\Http\Message\ServerRequestInterface
    {
        // 環境変数からサイトURLを取得
        $siteUrl = env('SITE_URL', 'https://localhost');
        $uri = $siteUrl . $request->getRequestTarget();

        // ヘッダーを取得
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if($values) {
                $headers[$name] = $values;
            }
        }

        // client_credentials認証のためにAuthorizationヘッダーを処理
        if ($request->is('post')) {
            $postData = $request->getData();

            // POSTデータにclient_idとclient_secretがある場合、Basic認証ヘッダーに変換
            if (isset($postData['client_id']) && isset($postData['client_secret'])) {
                $credentials = base64_encode($postData['client_id'] . ':' . $postData['client_secret']);
                $headers['Authorization'] = ['Basic ' . $credentials];

                // client_secretをPOSTデータから除去（OAuth2ライブラリがAuthorizationヘッダーから取得するため）
                unset($postData['client_secret']);
            }
        }

        // ボディコンテンツを取得
        $body = Stream::create('');
        if ($request->is('post')) {
            $postData = $request->getData();

            // client_secretが除去された後のPOSTデータを使用
            if (!empty($postData)) {
                $bodyContent = http_build_query($postData);
                $body = Stream::create($bodyContent);
                $headers['Content-Type'] = ['application/x-www-form-urlencoded'];
            }
        }

        // PSR-7リクエストを作成
        $psrRequest = new ServerRequest(
            $request->getMethod(),
            $uri,
            $headers,
            $body
        );

        // POSTデータをparsedBodyとして設定
        if ($request->is('post')) {
            $postData = $request->getData();

            // client_secretを除去したデータを設定
            if (isset($postData['client_secret'])) {
                unset($postData['client_secret']);
            }

            $psrRequest = $psrRequest->withParsedBody($postData);
        }

        return $psrRequest;
    }
}
