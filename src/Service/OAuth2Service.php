<?php
declare(strict_types=1);

namespace CuMcp\Service;

use CuMcp\Model\Repository\OAuth2AccessTokenRepository;
use CuMcp\Model\Repository\OAuth2ClientRepository;
use CuMcp\Model\Repository\OAuth2ScopeRepository;
use Defuse\Crypto\Key;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;

/**
 * OAuth2 Service
 *
 * OAuth2認証サーバーとリソースサーバーを管理
 */
class OAuth2Service
{
    /**
     * 認証サーバー
     *
     * @var AuthorizationServer|null
     */
    private ?AuthorizationServer $authorizationServer = null;

    /**
     * リソースサーバー
     *
     * @var ResourceServer|null
     */
    private ?ResourceServer $resourceServer = null;

    /**
     * OAuth2認証サーバーを取得
     *
     * @return AuthorizationServer
     */
    public function getAuthorizationServer(): AuthorizationServer
    {
        if ($this->authorizationServer === null) {
            $this->authorizationServer = $this->createAuthorizationServer();
        }

        return $this->authorizationServer;
    }

    /**
     * OAuth2リソースサーバーを取得
     *
     * @return ResourceServer
     */
    public function getResourceServer(): ResourceServer
    {
        if ($this->resourceServer === null) {
            $this->resourceServer = $this->createResourceServer();
        }

        return $this->resourceServer;
    }

    /**
     * 認証サーバーを作成
     *
     * @return AuthorizationServer
     */
    private function createAuthorizationServer(): AuthorizationServer
    {
        // リポジトリを作成（シングルトンインスタンスを使用）
        $clientRepository = new OAuth2ClientRepository();
        $accessTokenRepository = OAuth2AccessTokenRepository::getInstance();
        $scopeRepository = new OAuth2ScopeRepository();

        // 暗号化キーを取得
        $privateKey = $this->getPrivateKey();
        $encryptionKey = $this->getEncryptionKey();

        // 認証サーバーを作成
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
            $encryptionKey
        );

        // Client Credentialsグラントを有効化（デフォルトで1時間）
        $clientCredentialsGrant = new ClientCredentialsGrant();
        $server->enableGrantType(
            $clientCredentialsGrant,
            new \DateInterval('PT1H')
        );

        return $server;
    }

    /**
     * リソースサーバーを作成
     *
     * @return ResourceServer
     */
    private function createResourceServer(): ResourceServer
    {
        $accessTokenRepository = OAuth2AccessTokenRepository::getInstance();
        $publicKey = $this->getPublicKey();

        return new ResourceServer(
            $accessTokenRepository,
            $publicKey
        );
    }

    /**
     * 秘密鍵を取得
     *
     * @return CryptKey
     */
    private function getPrivateKey(): CryptKey
    {
        $keyPath = CONFIG . 'oauth2_private.key';

        if (!file_exists($keyPath)) {
            $this->generateKeyPair();
        }

        return new CryptKey($keyPath, null, false);
    }

    /**
     * 公開鍵を取得
     *
     * @return CryptKey
     */
    private function getPublicKey(): CryptKey
    {
        $keyPath = CONFIG . 'oauth2_public.key';

        if (!file_exists($keyPath)) {
            $this->generateKeyPair();
        }

        return new CryptKey($keyPath, null, false);
    }

    /**
     * 暗号化キーを取得
     *
     * @return string
     */
    private function getEncryptionKey(): string
    {
        // 基本的なランダムキー生成（毎回生成）
        return base64_encode(random_bytes(32));
    }

    /**
     * キーペアを生成
     *
     * @return void
     */
    private function generateKeyPair(): void
    {
        $privateKeyPath = CONFIG . 'oauth2_private.key';
        $publicKeyPath = CONFIG . 'oauth2_public.key';

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
     * アクセストークンを検証
     *
     * @param string $token
     * @return array|null
     */
    public function validateAccessToken(string $token): ?array
    {
        try {
            $resourceServer = $this->getResourceServer();

            // PSR-7リクエストを直接作成（テスト環境でも動作するように）
            $request = new \Nyholm\Psr7\ServerRequest(
                'GET',
                'https://localhost',
                ['Authorization' => 'Bearer ' . $token]
            );

            $request = $resourceServer->validateAuthenticatedRequest($request);

            // トークンが有効な場合、クライアント情報とスコープを返す
            return [
                'client_id' => $request->getAttribute('oauth_client_id'),
                'user_id' => $request->getAttribute('oauth_user_id'),
                'scopes' => $request->getAttribute('oauth_scopes', [])
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
