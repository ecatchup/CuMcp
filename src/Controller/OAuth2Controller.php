<?php
declare(strict_types=1);

namespace CuMcp\Controller;

use App\Controller\AppController;
use CuMcp\OAuth2\Service\OAuth2Service;
use CuMcp\OAuth2\Service\OAuth2ClientRegistrationService;
use CuMcp\OAuth2\Repository\OAuth2ClientRepository;
use Cake\Http\Response;
use CuMcp\Lib\OAuth2Util;
use Nyholm\Psr7\Response as Psr7Response;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * OAuth2 Controller
 *
 * OAuth2認証エンドポイントを提供（認証不要なエンドポイントのみ）
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
     * OAuth2クライアント登録サービス
     *
     * @var OAuth2ClientRegistrationService
     */
    private OAuth2ClientRegistrationService $clientRegistrationService;

    /**
     * 初期化
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->oauth2Service = new OAuth2Service();

        // クライアント登録サービスを初期化
        $clientRepository = new OAuth2ClientRepository();
        $this->clientRegistrationService = new OAuth2ClientRegistrationService($clientRepository);

        // CORS設定
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * OPTIONSリクエスト対応（CORS対応）
     *
     * @return Response
     */
    public function options(): Response
    {
        return $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withStatus(200);
    }

    /**
     * トークン発行エンドポイント
     *
     * @return Response
     */
    public function token(): Response
    {
        try {
            // PSR-7リクエストを作成
            $psrRequest = OAuth2Util::createPsr7Request($this->request);

            // OAuth2サーバーでアクセストークンリクエストを処理
            $psrResponse = $this->oauth2Service->getAuthorizationServer()
                ->respondToAccessTokenRequest($psrRequest, new Psr7Response());

            // PSR-7レスポンスをCakePHPレスポンスに変換
            // 一部のPSR-7実装では、書き込み後にストリームポインタが末尾にあるため、
            // getContents() が空文字を返すのを防ぐために rewind してから取得する
            $psrBody = $psrResponse->getBody();
            if ($psrBody->isSeekable()) {
                $psrBody->rewind();
            }
            $bodyString = $psrBody->getContents();

            return $this->response
                ->withStatus($psrResponse->getStatusCode())
                ->withType('application/json')
                ->withStringBody($bodyString);
        } catch (OAuthServerException $exception) {
            // OAuth2の仕様に沿ったエラーレスポンスを返す
            $errorPsrResponse = $exception->generateHttpResponse(new Psr7Response());
            $errorBody = $errorPsrResponse->getBody();
            if ($errorBody->isSeekable()) {
                $errorBody->rewind();
            }
            $errorString = $errorBody->getContents();

            $cakeResponse = $this->response
                ->withStatus($errorPsrResponse->getStatusCode())
                ->withType('application/json')
                ->withStringBody($errorString);

            // 必要に応じてヘッダーも反映（例: WWW-Authenticate）
            foreach ($errorPsrResponse->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    $cakeResponse = $cakeResponse->withHeader($name, $value);
                }
            }

            return $cakeResponse;
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
            // 現在のリクエストからベースURLを動的に取得
            $scheme = $this->request->is('https') ? 'https' : 'http';
            $host = $this->request->getHeaderLine('Host');
            if (!$host) {
                $host = $this->request->getEnv('HTTP_HOST') ?: 'localhost';
            }
            $baseUrl = $scheme . '://' . $host;

            $metadata = [
                'resource' => $baseUrl . '/cu-mcp/mcp-proxy',
                'authorization_servers' => [$baseUrl],
                'scopes_supported' => ['mcp:read', 'mcp:write'],
                'bearer_methods_supported' => ['header'],
                'introspection_endpoint' => $baseUrl . '/cu-mcp/oauth2/verify',
                'resource_registration_endpoint' => $baseUrl . '/cu-mcp/oauth2/client-info'
            ];

            return $this->response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
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
            // 現在のリクエストからベースURLを動的に取得
            $scheme = $this->request->is('https') ? 'https' : 'http';
            $host = $this->request->getHeaderLine('Host');
            if (!$host) {
                $host = $this->request->getEnv('HTTP_HOST') ?: 'localhost';
            }
            $baseUrl = $scheme . '://' . $host;

            $metadata = [
                // RFC 8414 必須項目
                'issuer' => $baseUrl,
                'authorization_endpoint' => $baseUrl . '/baser/admin/cu-mcp/oauth2/authorize',
                'token_endpoint' => $baseUrl . '/cu-mcp/oauth2/token',
                'response_types_supported' => ['code'],

                // 両方のGrantをサポート
                'grant_types_supported' => ['authorization_code', 'client_credentials', 'refresh_token'],
                'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
                'scopes_supported' => ['mcp:read', 'mcp:write'],

                // PKCE サポート（ChatGPTで推奨される）
                'code_challenge_methods_supported' => ['S256', 'plain'],

                // 実装済みエンドポイント
                'introspection_endpoint' => $baseUrl . '/cu-mcp/oauth2/verify',
                'introspection_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],

                // Authorization Code Grant関連
                'revocation_endpoint' => $baseUrl . '/cu-mcp/oauth2/revoke',
                'revocation_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],

                'registration_endpoint' => $baseUrl . '/cu-mcp/oauth2/register'
            ];

            return $this->response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
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
     * 動的クライアント登録エンドポイント (RFC 7591)
     * POST /cu-mcp/oauth2/register
     *
     * @return Response
     */
    public function register(): Response
    {
        if (!$this->request->is('post')) {
            return $this->response
                ->withStatus(405)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'invalid_request',
                    'error_description' => 'Only POST method is supported'
                ]));
        }

        try {
            // JSONリクエストデータを取得
            $requestData = [];
            $contentType = $this->request->getHeaderLine('Content-Type');

            // CakePHPは自動的にJSONデータをパースしてgetData()で取得可能
            $requestData = $this->request->getData();

            // データが空の場合のみ、手動でJSONパースを実行
            if (empty($requestData) && strpos($contentType, 'application/json') !== false) {
                $body = $this->request->getBody()->getContents();
                if (!empty($body)) {
                    $requestData = json_decode($body, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return $this->response
                            ->withStatus(400)
                            ->withType('application/json')
                            ->withStringBody(json_encode([
                                'error' => 'invalid_request',
                                'error_description' => 'Invalid JSON in request body'
                            ]));
                    }
                }
            }

            // 環境変数からサイトURLを取得
            $siteUrl = env('SITE_URL', 'https://localhost');
            $baseUrl = rtrim($siteUrl, '/');

            // クライアントを登録
            $client = $this->clientRegistrationService->registerClient($requestData, $baseUrl);

            // RFC7591準拠のレスポンスを返す
            return $this->response
                ->withStatus(201)
                ->withType('application/json')
                ->withStringBody(json_encode($client->toRegistrationResponse(), JSON_PRETTY_PRINT));

        } catch (Exception $exception) {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'invalid_client_metadata',
                    'error_description' => $exception->getMessage()
                ]));
        }
    }

    /**
     * クライアント設定エンドポイント (RFC 7591)
     * GET /cu-mcp/oauth2/register/{client_id}
     * PUT /cu-mcp/oauth2/register/{client_id}
     * DELETE /cu-mcp/oauth2/register/{client_id}
     *
     * @param string $clientId クライアントID
     * @return Response
     */
    public function clientConfiguration(string $clientId): Response
    {
        // 登録アクセストークンを取得
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->response
                ->withStatus(401)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'invalid_token',
                    'error_description' => 'Registration access token is required'
                ]));
        }

        $registrationAccessToken = substr($authHeader, 7);

        try {
            if ($this->request->is('get')) {
                // クライアント情報の取得
                $client = $this->clientRegistrationService->getClient($clientId, $registrationAccessToken);

                if (!$client) {
                    return $this->response
                        ->withStatus(401)
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'error' => 'invalid_token',
                            'error_description' => 'Invalid registration access token or client not found'
                        ]));
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode($client->toRegistrationResponse(), JSON_PRETTY_PRINT));

            } elseif ($this->request->is('put')) {
                // クライアント情報の更新
                // CakePHPは自動的にJSONデータをパースしてgetData()で取得可能
                $requestData = $this->request->getData();

                // データが空の場合のみ、手動でJSONパースを実行
                $contentType = $this->request->getHeaderLine('Content-Type');
                if (empty($requestData) && strpos($contentType, 'application/json') !== false) {
                    $body = $this->request->getBody()->getContents();
                    if (!empty($body)) {
                        $requestData = json_decode($body, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return $this->response
                                ->withStatus(400)
                                ->withType('application/json')
                                ->withStringBody(json_encode([
                                    'error' => 'invalid_request',
                                    'error_description' => 'Invalid JSON in request body'
                                ]));
                        }
                    }
                }

                $client = $this->clientRegistrationService->updateClient($clientId, $registrationAccessToken, $requestData);

                if (!$client) {
                    return $this->response
                        ->withStatus(401)
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'error' => 'invalid_token',
                            'error_description' => 'Invalid registration access token or client not found'
                        ]));
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode($client->toRegistrationResponse(), JSON_PRETTY_PRINT));

            } elseif ($this->request->is('delete')) {
                // クライアントの削除
                $success = $this->clientRegistrationService->deleteClient($clientId, $registrationAccessToken);

                if (!$success) {
                    return $this->response
                        ->withStatus(401)
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'error' => 'invalid_token',
                            'error_description' => 'Invalid registration access token or client not found'
                        ]));
                }

                return $this->response->withStatus(204); // No Content

            } else {
                return $this->response
                    ->withStatus(405)
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'error' => 'invalid_request',
                        'error_description' => 'Only GET, PUT, DELETE methods are supported'
                    ]));
            }

        } catch (Exception $exception) {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'invalid_client_metadata',
                    'error_description' => $exception->getMessage()
                ]));
        }
    }

}
