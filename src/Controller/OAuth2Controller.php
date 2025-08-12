<?php
declare(strict_types=1);

namespace CuMcp\Controller;

use App\Controller\AppController;
use CuMcp\Service\OAuth2Service;
use Cake\Http\Response;
use Nyholm\Psr7\Response as Psr7Response;

/**
 * OAuth2 Controller
 *
 * OAuth2認証エンドポイントを提供
 */
class OAuth2Controller extends AppController
{
    /**
     * OAuth2サービス
     *
     * @var OAuth2Service
     */
    private OAuth2Service $oauth2Service;

    /**
     * 初期化
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->oauth2Service = new OAuth2Service();

        // CORS設定
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * OPTIONSリクエスト対応
     *
     * @return Response
     */
    public function options(): Response
    {
        return $this->response->withStatus(200);
    }

    /**
     * トークン発行エンドポイント
     *
     * @return Response
     */
    public function token(): Response
    {
        try {
            // CakePHPのリクエストからPSR-7リクエストに変換
            $cakeRequest = $this->request;
            $body = $cakeRequest->getBody()->getContents();

            // ヘッダーを取得
            $headers = $cakeRequest->getHeaders();

            // POST データを取得
            $postData = [];
            if ($cakeRequest->is('post')) {
                $postData = $cakeRequest->getData();

                // クライアント認証情報がリクエストボディに含まれている場合、HTTP Basic認証ヘッダーに変換
                if (isset($postData['client_id']) && isset($postData['client_secret'])) {
                    $credentials = base64_encode($postData['client_id'] . ':' . $postData['client_secret']);
                    $headers['Authorization'] = ['Basic ' . $credentials];

                    // リクエストボディからクライアント認証情報を削除
                    unset($postData['client_secret']);
                }
            }

            // PSR-7リクエストを作成
            $request = new \Nyholm\Psr7\ServerRequest(
                $cakeRequest->getMethod(),
                $cakeRequest->getUri()->__toString(),
                $headers,
                $body
            );

            // POST データを設定
            if ($cakeRequest->is('post')) {
                $request = $request->withParsedBody($postData);
            }

            // PSR-7レスポンスを作成
            $psrResponse = new Psr7Response();

            // OAuth2サーバーでトークンを発行
            $authServer = $this->oauth2Service->getAuthorizationServer();
            $psrResponse = $authServer->respondToAccessTokenRequest($request, $psrResponse);

            // CakePHPレスポンスに変換
            $this->response = $this->response->withStatus($psrResponse->getStatusCode());

            foreach ($psrResponse->getHeaders() as $name => $values) {
                $this->response = $this->response->withHeader($name, implode(', ', $values));
            }

            // ストリームを巻き戻してから内容を取得
            $responseStream = $psrResponse->getBody();
            $responseStream->rewind();
            $responseBody = $responseStream->getContents();

            $this->response = $this->response->withStringBody($responseBody);

            return $this->response;

        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            // OAuth2エラーレスポンス
            $psrResponse = new Psr7Response();
            $psrResponse = $exception->generateHttpResponse($psrResponse);

            // ストリームを巻き戻してから内容を取得
            $errorStream = $psrResponse->getBody();
            $errorStream->rewind();
            $errorBody = $errorStream->getContents();

            $this->response = $this->response->withStatus($psrResponse->getStatusCode());
            $this->response = $this->response->withType('application/json');

            foreach ($psrResponse->getHeaders() as $name => $values) {
                $this->response = $this->response->withHeader($name, implode(', ', $values));
            }

            $this->response = $this->response->withStringBody($errorBody);

            return $this->response;

        } catch (\Exception $exception) {
            // 一般的なエラーレスポンス
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'An unexpected error occurred.',
                    'message' => $exception->getMessage()
                ]));
        }
    }

    /**
     * トークン検証エンドポイント
     *
     * @return Response
     */
    public function verify(): Response
    {
        try {
            $authHeader = $this->request->getHeaderLine('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return $this->response
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_token',
                        'error_description' => 'The access token is missing or invalid.'
                    ]));
            }

            $token = substr($authHeader, 7); // "Bearer "を除去
            $tokenData = $this->oauth2Service->validateAccessToken($token);

            if (!$tokenData) {
                return $this->response
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_token',
                        'error_description' => 'The access token is invalid or expired.'
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'valid' => true,
                    'client_id' => $tokenData['client_id'],
                    'user_id' => $tokenData['user_id'],
                    'scopes' => $tokenData['scopes']
                ]));

        } catch (\Exception $exception) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'An unexpected error occurred.',
                    'message' => $exception->getMessage()
                ]));
        }
    }

    /**
     * クライアント情報取得エンドポイント
     *
     * @return Response
     */
    public function clientInfo(): Response
    {
        try {
            $authHeader = $this->request->getHeaderLine('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return $this->response
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'unauthorized',
                        'error_description' => 'Authentication required.'
                    ]));
            }

            $token = substr($authHeader, 7);
            $tokenData = $this->oauth2Service->validateAccessToken($token);

            if (!$tokenData) {
                return $this->response
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_token',
                        'error_description' => 'The access token is invalid or expired.'
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'client_id' => $tokenData['client_id'],
                    'scopes' => $tokenData['scopes'],
                    'authenticated' => true
                ]));

        } catch (\Exception $exception) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'An unexpected error occurred.'
                ]));
        }
    }

    /**
     * OAuth 2.0 保護リソースメタデータエンドポイント (RFC 9728)
     *
     * @return Response
     */
    public function protectedResourceMetadata(): Response
    {
        try {
            // 環境変数からサイトURLを取得
            $siteUrl = env('SITE_URL', 'https://localhost');
            $baseUrl = rtrim($siteUrl, '/');

            $metadata = [
                'resource' => $baseUrl . '/cu-mcp',
                'authorization_servers' => [
                    $baseUrl . '/cu-mcp/oauth2'
                ],
                'scopes_supported' => ['read', 'write', 'admin'],
                'bearer_methods_supported' => ['header'],
                'introspection_endpoint' => $baseUrl . '/cu-mcp/oauth2/verify',
                'resource_registration_endpoint' => $baseUrl . '/cu-mcp/oauth2/client-info'
            ];

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($metadata, JSON_PRETTY_PRINT));

        } catch (\Exception $exception) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'Failed to generate protected resource metadata.',
                    'debug_message' => $exception->getMessage()
                ]));
        }
    }

    /**
     * OAuth 2.0 認可サーバーメタデータエンドポイント (RFC 8414)
     *
     * @return Response
     */
    public function authorizationServerMetadata(): Response
    {
        try {
            // 環境変数からサイトURLを取得
            $siteUrl = env('SITE_URL', 'https://localhost');
            $baseUrl = rtrim($siteUrl, '/');
            $issuer = $baseUrl . '/cu-mcp/oauth2';

            $metadata = [
                // RFC 8414 必須項目
                'issuer' => $issuer,
                'authorization_endpoint' => $issuer . '/authorize',
                'token_endpoint' => $issuer . '/token',
                'response_types_supported' => ['code', 'token'],

                // 両方のGrantをサポート
                'grant_types_supported' => ['authorization_code', 'client_credentials', 'refresh_token'],
                'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
                'scopes_supported' => ['read', 'write', 'admin'],

                // 実装済みエンドポイント
                'introspection_endpoint' => $issuer . '/verify',
                'introspection_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],

                // Authorization Code Grant関連
                'code_challenge_methods_supported' => ['plain', 'S256'],
                'revocation_endpoint' => $issuer . '/revoke',
                'revocation_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post']
            ];

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($metadata, JSON_PRETTY_PRINT));

        } catch (\Exception $exception) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'Failed to generate authorization server metadata.',
                    'debug_message' => $exception->getMessage()
                ]));
        }
    }

    /**
     * 認可エンドポイント
     * Authorization Code Grantの開始点
     *
     * @return Response
     */
    public function authorize(): Response
    {
        try {
            // ユーザーがログインしているかチェック
            $user = $this->Authentication->getIdentity();
            if (!$user) {
                // ログインページにリダイレクト（認可リクエストパラメータを保持）
                $this->Flash->set('認証が必要です。ログインしてください。');
                return $this->redirect([
                    'plugin' => null,
                    'controller' => 'Users',
                    'action' => 'login',
                    '?' => [
                        'redirect' => $this->request->getRequestTarget()
                    ]
                ]);
            }

            $request = $this->request;
            
            // 必須パラメータをチェック
            $clientId = $request->getQuery('client_id');
            $responseType = $request->getQuery('response_type');
            $redirectUri = $request->getQuery('redirect_uri');
            $state = $request->getQuery('state');
            $scope = $request->getQuery('scope', '');

            if (!$clientId || !$responseType || !$redirectUri) {
                return $this->response
                    ->withStatus(400)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_request',
                        'error_description' => 'Missing required parameters: client_id, response_type, redirect_uri'
                    ]));
            }

            if ($responseType !== 'code') {
                return $this->response
                    ->withStatus(400)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'unsupported_response_type',
                        'error_description' => 'Only response_type=code is supported'
                    ]));
            }

            // クライアントの妥当性をチェック
            $clientRepository = new \CuMcp\Model\Repository\OAuth2ClientRepository();
            $client = $clientRepository->getClientEntity($clientId);
            
            if (!$client || !$clientRepository->validateClient($clientId, null, null)) {
                return $this->response
                    ->withStatus(400)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_client',
                        'error_description' => 'Invalid client_id'
                    ]));
            }

            // リダイレクトURIの妥当性をチェック
            if (!in_array($redirectUri, $client->getRedirectUri())) {
                return $this->response
                    ->withStatus(400)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_redirect_uri',
                        'error_description' => 'Invalid redirect_uri'
                    ]));
            }

            // POSTリクエストの場合は認可処理
            if ($this->request->is('post')) {
                $action = $this->request->getData('action');
                
                if ($action === 'approve') {
                    // 認可コードを生成
                    $authCode = bin2hex(random_bytes(32));
                    
                    // 認可コードを保存（実際にはデータベースに保存）
                    $this->oauth2Service->storeAuthorizationCode([
                        'code' => $authCode,
                        'client_id' => $clientId,
                        'user_id' => $user->getIdentifier(),
                        'redirect_uri' => $redirectUri,
                        'scope' => $scope,
                        'expires_at' => time() + 600, // 10分間有効
                    ]);

                    // リダイレクトURIに認可コードを付けてリダイレクト
                    $params = ['code' => $authCode];
                    if ($state) {
                        $params['state'] = $state;
                    }
                    
                    $redirectUrl = $redirectUri . '?' . http_build_query($params);
                    return $this->redirect($redirectUrl);

                } elseif ($action === 'deny') {
                    // アクセス拒否
                    $params = [
                        'error' => 'access_denied',
                        'error_description' => 'The user denied the request'
                    ];
                    if ($state) {
                        $params['state'] = $state;
                    }
                    
                    $redirectUrl = $redirectUri . '?' . http_build_query($params);
                    return $this->redirect($redirectUrl);
                }
            }

            // 認可画面を表示
            $this->set([
                'client' => $client,
                'clientId' => $clientId,
                'redirectUri' => $redirectUri,
                'scope' => $scope,
                'state' => $state,
                'user' => $user
            ]);
            
            return $this->render('authorize');

        } catch (\Exception $exception) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'server_error',
                    'error_description' => 'An unexpected error occurred.',
                    'message' => $exception->getMessage()
                ]));
        }
    }
}
