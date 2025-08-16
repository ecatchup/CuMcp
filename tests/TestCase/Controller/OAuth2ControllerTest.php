<?php
declare(strict_types=1);

namespace CuMcp\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Core\Configure;

/**
 * OAuth2Controller Test Case
 * 認証不要なOAuth2エンドポイントのテスト
 */
class OAuth2ControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->loadPlugins(['CuMcp']);
        parent::setUp();

        // OAuth2設定をセットアップ
        Configure::write('CuMcp.OAuth2.clients', [
            'mcp-client' => [
                'name' => 'MCP Server Client',
                'secret' => 'mcp-secret-key',
                'redirect_uris' => ['http://localhost'],
                'grants' => ['client_credentials'],
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
     * Test token endpoint with valid client credentials (no auth required)
     *
     * @return void
     */
    public function testTokenEndpointWithValidCredentials(): void
    {
        // 認証なしでtokenエンドポイントをテスト
        $this->post('/cu-mcp/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'mcp-client',
            'client_secret' => 'mcp-secret-key',
            'scope' => 'read write'
        ]);

        $this->assertResponseOk();
        $this->assertResponseCode(200);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Response should be valid JSON');
        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('token_type', $response);
        $this->assertArrayHasKey('expires_in', $response);
        $this->assertEquals('Bearer', $response['token_type']);
    }

    /**
     * Test authorization server metadata endpoint (no auth required)
     *
     * @return void
     */
    public function testAuthorizationServerMetadata(): void
    {
        $this->get('/.well-known/oauth-authorization-server');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('issuer', $response);
        $this->assertArrayHasKey('token_endpoint', $response);
        $this->assertArrayHasKey('authorization_endpoint', $response);
    }

    /**
     * Test protected resource metadata endpoint (no auth required)
     *
     * @return void
     */
    public function testProtectedResourceMetadata(): void
    {
        $this->get('/.well-known/oauth-protected-resource');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('resource', $response);
        $this->assertArrayHasKey('authorization_servers', $response);
    }

    /**
     * Test client registration endpoint (no auth required)
     *
     * @return void
     */
    public function testClientRegistration(): void
    {
        $this->post('/cu-mcp/oauth2/register', [
            'client_name' => 'Test Client',
            'client_uri' => 'http://localhost',
            'redirect_uris' => ['http://localhost/callback'],
            'grant_types' => ['client_credentials'],
            'response_types' => ['code'],
            'scope' => 'read write'
        ]);

        $this->assertResponseCode(201);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('client_id', $response);
        $this->assertArrayHasKey('client_secret', $response);
    }
}
