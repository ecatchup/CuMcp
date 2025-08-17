<?php
declare(strict_types=1);

namespace CuMcp\Test\TestCase\Controller\Admin;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\Core\Configure;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

/**
 * Admin OAuth2Controller Test Case
 * 認証が必要なOAuth2エンドポイントのテスト
 */
class OAuth2ControllerTest extends BcTestCase
{
    use IntegrationTestTrait;
    use ScenarioAwareTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->loadPlugins(['CuMcp']);
        parent::setUp();
        $this->loadFixtureScenario(InitAppScenario::class);
        // OAuth2設定をセットアップ
        Configure::write('CuMcp.OAuth2.clients', [
            'mcp-client' => [
                'name' => 'MCP Server Client',
                'secret' => 'mcp-secret-key',
                'redirect_uris' => ['http://localhost'],
                'grants' => ['authorization_code'],
                'scopes' => ['read', 'write']
            ]
        ]);

        Configure::write('CuMcp.OAuth2.scopes', [
            'read' => 'データの読み取り',
            'write' => 'データの書き込み',
            'admin' => '管理者権限'
        ]);

        Configure::write('OAuth2.accessTokenTTL', 'PT1H');

        // テスト用のOAuth2キーペアが存在することを確認
        $privateKeyPath = CONFIG . 'oauth2_private.key';
        $publicKeyPath = CONFIG . 'oauth2_public.key';

        if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
            $this->generateTestKeys($privateKeyPath, $publicKeyPath);
        }

        // Admin配下のテスト用設定
        $this->configRequest([
            'environment' => [
                'HTTPS' => 'off'
            ]
        ]);
    }

    /**
     * テスト用のRSAキーペアを生成
     */
    private function generateTestKeys(string $privateKeyPath, string $publicKeyPath): void
    {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);

        $pubKey = openssl_pkey_get_details($res);
        $publicKey = $pubKey["key"];

        file_put_contents($privateKeyPath, $privKey);
        file_put_contents($publicKeyPath, $publicKey);
    }

    /**
     * Test authorize endpoint with authenticated user
     *
     * @return void
     */
    public function testAuthorizeEndpointWithAuthenticatedUser(): void
    {
        $this->loginAdmin($this->getRequest());

        // 認可リクエストのパラメータ
        $params = [
            'client_id' => 'mcp-client',
            'client_secret' => 'mcp-secret-key',
            'response_type' => 'code',
            'redirect_uri' => 'http://localhost',
            'scope' => 'read write',
            'state' => 'test-state'
        ];

        $this->get('/baser/admin/cu-mcp/oauth2/authorize?' . http_build_query($params));

        // 認証済みユーザーなので認可画面が表示される
        $this->assertResponseOk();
    }

    /**
     * Test authorize endpoint without authentication
     *
     * @return void
     */
    public function testAuthorizeEndpointWithoutAuthentication(): void
    {
        // 認証なしでauthorizeエンドポイントにアクセス
        $this->get('/baser/admin/cu-mcp/oauth2/authorize', [
            'client_id' => 'mcp-client',
            'response_type' => 'code',
            'redirect_uri' => 'http://localhost'
        ]);

        // 認証が必要なため、リダイレクトが返される
        $this->assertResponseCode(302);
    }

    public function testIntegration(): void
    {
        // MPCサーバーの接続ポイントにGETリクエストを送信
        $this->get('/mcp');
        $this->assertResponseCode(405);

        // oauth-protected-resource にリクエストを送信
        $this->get('/.well-known/oauth-protected-resource');
        $metadata = json_decode((string)$this->_response->getBody(), true);
        $authorizationServer = $metadata['authorization_servers'][0];
        $this->assertTextContains('/cu-mcp/oauth2', $authorizationServer);

        // oauth-authorization-server にリクエストを送信
        $this->get('/.well-known/oauth-authorization-server');
        $metadata = json_decode((string)$this->_response->getBody(), true);
        $registrationEndpoint = $metadata['registration_endpoint'];

        // クライアント登録エンドポイントにPOSTリクエストを送信
        $this->post($registrationEndpoint, [
            'client_name' => 'Test Client',
            'client_uri' => 'http://localhost',
            'redirect_uris' => ['http://localhost/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'read write'
        ]);
        $metadata = json_decode((string)$this->_response->getBody(), true);
        $this->assertResponseCode(201);
        $this->assertArrayHasKey('client_id', $metadata);

        // 認可リクエスト
        $this->get('/baser/admin/cu-mcp/oauth2/authorize?' . http_build_query([
            'client_id' => $metadata['client_id'],
            'client_secret' => $metadata['client_secret'],
            'response_type' => 'code',
            'redirect_uri' => $metadata['redirect_uris'][0]
        ]));
        $this->assertResponseCode(302);

        $this->loginAdmin($this->getRequest());
        $this->get('/baser/admin/cu-mcp/oauth2/authorize?' . http_build_query([
            'client_id' => $metadata['client_id'],
            'client_secret' => $metadata['client_secret'],
            'response_type' => 'code',
            'redirect_uri' => $metadata['redirect_uris'][0]
        ]));
        $this->assertResponseCode(200);

        // 認可承認
        $this->post('/baser/admin/cu-mcp/oauth2/authorize?' . http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $metadata['client_id'],
            'client_secret' => $metadata['client_secret'],
            'response_type' => 'code',
            'redirect_uri' => $metadata['redirect_uris'][0]
        ]), ['action' => 'approve']);
        $this->assertResponseCode(302);
        $redirectUrl = $this->_response->getHeaderLine('Location');
        $this->assertStringContainsString('code=', $redirectUrl);
        // 認可コードを取得
        $queryParams = [];
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $queryParams);
        $this->assertArrayHasKey('code', $queryParams);
        $authCode = $queryParams['code'];

        // 認可コードを使用してアクセストークンを取得
        $this->post('/cu-mcp/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => $metadata['redirect_uris'][0],
            'client_id' => $metadata['client_id'],
            'client_secret' => $metadata['client_secret'],
            'scope' => 'read write'
        ]);
        $this->assertResponseCode(200);
        $tokenData = json_decode((string)$this->_response->getBody(), true);
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'];

        // リフレッシュトークンが取得できていることを確認
        $this->assertArrayHasKey('refresh_token', $tokenData);
        $this->assertNotEmpty($refreshToken);

        // アクセストークンを使用してMCPサーバーのツールリストを取得
        $requestConfig= [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        // MCPプロキシ経由でtools/listを呼び出し
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'id' => 'test-tools-list',
            'method' => 'tools/list'
        ];
        $this->configRequest($requestConfig);
        $this->post('/mcp', json_encode($mcpRequest));
        $this->assertResponseCode(200);
        $this->assertContentType('application/json');

        $toolsResponse = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($toolsResponse, 'MCP tools list response should be valid JSON');
        $this->assertArrayHasKey('result', $toolsResponse);
        $this->assertArrayHasKey('tools', $toolsResponse['result']);
        $this->assertIsArray($toolsResponse['result']['tools']);

        // ツールリストの内、ブログ記事一覧の取得ツールを実行
        $tools = $toolsResponse['result']['tools'];
        // ツールリストに getBlogPostsが含まれていることを確認
        $this->assertTrue(in_array('getBlogPosts', array_column($tools, 'name')), 'getBlogPosts tool should be available');

        // ブログ記事一覧取得ツールを実行
        $blogRequest = [
            'jsonrpc' => '2.0',
            'id' => 'test-blog-tool',
            'method' => 'tools/call',
            'params' => [
                'name' => 'getBlogPosts',
                'arguments' => []
            ]
        ];
        $this->configRequest($requestConfig);
        $this->post('/mcp', json_encode($blogRequest));
        $this->assertResponseCode(200);

        $blogResponse = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($blogResponse);
        $this->assertArrayHasKey('result', $blogResponse);

        // リフレッシュトークンを使用して新しいアクセストークンを取得
        $this->post('/cu-mcp/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $metadata['client_id'],
            'client_secret' => $metadata['client_secret']
        ]);
        $this->assertResponseCode(200);
        $newTokenData = json_decode((string)$this->_response->getBody(), true);
        $newAccessToken = $newTokenData['access_token'];

        // 新しいアクセストークンが取得できていることを確認
        $this->assertArrayHasKey('access_token', $newTokenData);
        $this->assertNotEmpty($newAccessToken);
        $this->assertNotEquals($accessToken, $newAccessToken, 'New access token should be different from the original');

        // 新しいアクセストークンを使用してgetBlogPostツールを実行
        $newRequestConfig = [
            'headers' => [
                'Authorization' => 'Bearer ' . $newAccessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        // getBlogPostツールを実行（IDが必要な場合はダミーIDを使用）
        $blogPostRequest = [
            'jsonrpc' => '2.0',
            'id' => 'test-blog-post-tool',
            'method' => 'tools/call',
            'params' => [
                'name' => 'getBlogPost',
                'arguments' => [
                    'id' => 1 // ダミーID
                ]
            ]
        ];
        $this->configRequest($newRequestConfig);
        $this->post('/mcp', json_encode($blogPostRequest));

        // レスポンスコードが200または404（データが存在しない場合）であることを確認
        $this->assertTrue(
            in_array($this->_response->getStatusCode(), [200, 404]),
            'getBlogPost should return 200 (success) or 404 (not found)'
        );

        if ($this->_response->getStatusCode() === 200) {
            $blogPostResponse = json_decode((string)$this->_response->getBody(), true);
            $this->assertNotNull($blogPostResponse);
            $this->assertArrayHasKey('result', $blogPostResponse);
        }
    }

}
