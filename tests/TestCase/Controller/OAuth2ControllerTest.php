<?php
declare(strict_types=1);

namespace CuMcp\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Core\Configure;

/**
 * OAuth2Controller Test Case
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
                'secret' => 'mcp-secret-key', // 機密クライアントに変更
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
     * Test token endpoint with valid client credentials
     *
     * @return void
     */
    public function testTokenEndpointWithValidCredentials(): void
    {
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
     * Test token endpoint with invalid client credentials
     *
     * @return void
     */
    public function testTokenEndpointWithInvalidCredentials(): void
    {
        $this->post('/cu-mcp/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'invalid-client',
            'client_secret' => 'invalid-secret', // 無効なクライアント秘密キーを追加
            'scope' => 'read'
        ]);

        $this->assertResponseError();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Invalid client response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('invalid_client', $response['error']);
    }

    /**
     * Test OPTIONS request for CORS
     *
     * @return void
     */
    public function testOptionsRequest(): void
    {
        $this->configRequest([
            'method' => 'OPTIONS'
        ]);

        $this->_sendRequest('/cu-mcp/oauth2/token', 'OPTIONS', []);

        $this->assertResponseOk();
        $this->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Test verify endpoint with valid token
     *
     * @return void
     */
    public function testVerifyEndpointWithValidToken(): void
    {
        // まず有効なトークンを取得
        $tokenData = [
            'grant_type' => 'client_credentials',
            'client_id' => 'mcp-client',
            'client_secret' => 'mcp-secret-key'
        ];

        $this->post('/cu-mcp/oauth2/token', $tokenData);
        $this->assertResponseSuccess();

        $tokenResponse = json_decode((string)$this->_response->getBody(), true);
        $accessToken = $tokenResponse['access_token'];

        // 取得したトークンでverifyエンドポイントをテスト
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/verify');

        $this->assertResponseSuccess();
        $this->assertContentType('application/json');

        $verifyResponse = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($verifyResponse, 'Verify response should be valid JSON');
        $this->assertArrayHasKey('valid', $verifyResponse);
        $this->assertTrue($verifyResponse['valid']);
        $this->assertArrayHasKey('client_id', $verifyResponse);
        $this->assertEquals('mcp-client', $verifyResponse['client_id']);
        $this->assertArrayHasKey('scopes', $verifyResponse);
    }

    /**
     * Test verify endpoint with invalid token
     *
     * @return void
     */
    public function testVerifyEndpointWithInvalidToken(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer invalid-token-12345',
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/verify');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Invalid token response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('invalid_token', $response['error']);
        $this->assertArrayHasKey('error_description', $response);
    }

    /**
     * Test verify endpoint without Authorization header
     *
     * @return void
     */
    public function testVerifyEndpointWithoutAuthHeader(): void
    {
        $this->get('/cu-mcp/oauth2/verify');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'No auth header response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('invalid_token', $response['error']);
        $this->assertStringContainsString('missing or invalid', $response['error_description']);
    }

    /**
     * Test verify endpoint with malformed Authorization header
     *
     * @return void
     */
    public function testVerifyEndpointWithMalformedAuthHeader(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'InvalidFormat token123',
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/verify');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Malformed auth header response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('invalid_token', $response['error']);
    }

    /**
     * Test verify endpoint OPTIONS request for CORS
     *
     * @return void
     */
    public function testVerifyOptionsRequest(): void
    {
        $this->configRequest([
            'method' => 'OPTIONS'
        ]);

        $this->_sendRequest('/cu-mcp/oauth2/verify', 'OPTIONS', []);

        $this->assertResponseOk();
        $this->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Test client-info endpoint with valid token
     *
     * @return void
     */
    public function testClientInfoEndpointWithValidToken(): void
    {
        // まず有効なトークンを取得
        $tokenData = [
            'grant_type' => 'client_credentials',
            'client_id' => 'mcp-client',
            'client_secret' => 'mcp-secret-key',
            'scope' => 'read write'
        ];

        $this->post('/cu-mcp/oauth2/token', $tokenData);
        $this->assertResponseSuccess();

        $tokenResponse = json_decode((string)$this->_response->getBody(), true);
        $accessToken = $tokenResponse['access_token'];

        // 取得したトークンでclient-infoエンドポイントをテスト
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/client-info');

        $this->assertResponseSuccess();
        $this->assertContentType('application/json');

        $clientInfoResponse = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($clientInfoResponse, 'Client info response should be valid JSON');
        $this->assertArrayHasKey('client_id', $clientInfoResponse);
        $this->assertEquals('mcp-client', $clientInfoResponse['client_id']);
        $this->assertArrayHasKey('scopes', $clientInfoResponse);
        $this->assertArrayHasKey('authenticated', $clientInfoResponse);
        $this->assertTrue($clientInfoResponse['authenticated']);
    }

    /**
     * Test client-info endpoint with invalid token
     *
     * @return void
     */
    public function testClientInfoEndpointWithInvalidToken(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer invalid-token-12345',
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/client-info');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Invalid token response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('invalid_token', $response['error']);
        $this->assertArrayHasKey('error_description', $response);
    }

    /**
     * Test client-info endpoint without Authorization header
     *
     * @return void
     */
    public function testClientInfoEndpointWithoutAuthHeader(): void
    {
        $this->get('/cu-mcp/oauth2/client-info');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'No auth header response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('unauthorized', $response['error']);
        $this->assertStringContainsString('Authentication required', $response['error_description']);
    }

    /**
     * Test client-info endpoint with malformed Authorization header
     *
     * @return void
     */
    public function testClientInfoEndpointWithMalformedAuthHeader(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'InvalidFormat token123',
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->get('/cu-mcp/oauth2/client-info');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotNull($response, 'Malformed auth header response should be valid JSON');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('unauthorized', $response['error']);
    }

    /**
     * Test client-info endpoint OPTIONS request for CORS
     *
     * @return void
     */
    public function testClientInfoOptionsRequest(): void
    {
        $this->configRequest([
            'method' => 'OPTIONS'
        ]);

        $this->_sendRequest('/cu-mcp/oauth2/client-info', 'OPTIONS', []);

        $this->assertResponseOk();
        $this->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
