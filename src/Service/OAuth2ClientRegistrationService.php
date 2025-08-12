<?php
declare(strict_types=1);

namespace CuMcp\Service;

use CuMcp\Model\Entity\OAuth2Client;
use CuMcp\Model\Repository\OAuth2ClientRepository;
use Exception;

/**
 * OAuth2 動的クライアント登録サービス
 * RFC7591 OAuth 2.0 Dynamic Client Registration Protocol の実装
 */
class OAuth2ClientRegistrationService
{
    /**
     * OAuth2クライアントリポジトリ
     *
     * @var OAuth2ClientRepository
     */
    private OAuth2ClientRepository $clientRepository;

    /**
     * サポートされるグラントタイプ
     *
     * @var array
     */
    private array $supportedGrantTypes = [
        'authorization_code',
        'client_credentials',
        'refresh_token'
    ];

    /**
     * サポートされるレスポンスタイプ
     *
     * @var array
     */
    private array $supportedResponseTypes = [
        'code'
    ];

    /**
     * サポートされるトークンエンドポイント認証方法
     *
     * @var array
     */
    private array $supportedAuthMethods = [
        'client_secret_basic',
        'client_secret_post',
        'none'
    ];

    /**
     * サポートされるスコープ
     *
     * @var array
     */
    private array $supportedScopes = [
        'read',
        'write',
        'admin'
    ];

    /**
     * コンストラクタ
     *
     * @param OAuth2ClientRepository $clientRepository
     */
    public function __construct(OAuth2ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * 動的クライアント登録
     *
     * @param array $requestData リクエストデータ
     * @param string $baseUrl ベースURL
     * @return OAuth2Client
     * @throws Exception
     */
    public function registerClient(array $requestData, string $baseUrl): OAuth2Client
    {
        // リクエストデータの検証
        $this->validateRegistrationRequest($requestData);

        // クライアントIDとシークレットを生成
        $clientId = $this->generateClientId();
        $clientSecret = null;
        $tokenEndpointAuthMethod = $requestData['token_endpoint_auth_method'] ?? 'client_secret_basic';

        // 機密クライアントの場合はシークレットを生成
        if ($tokenEndpointAuthMethod !== 'none') {
            $clientSecret = $this->generateClientSecret();
        }

        // 現在時刻を取得
        $issuedAt = time();
        $secretExpiresAt = null;

        // 登録アクセストークンを生成
        $registrationAccessToken = $this->generateRegistrationAccessToken();
        $registrationClientUri = $baseUrl . '/cu-mcp/oauth2/register/' . $clientId;

        // クライアントオブジェクトを作成
        $client = new OAuth2Client(
            $clientId,
            $requestData['client_name'] ?? 'Dynamic Client',
            $requestData['redirect_uris'] ?? [],
            $clientSecret,
            $requestData['grant_types'] ?? ['authorization_code'],
            $this->parseScopes($requestData['scope'] ?? ''),
            $registrationAccessToken,
            $registrationClientUri,
            $issuedAt,
            $secretExpiresAt,
            $tokenEndpointAuthMethod,
            $requestData['contacts'] ?? [],
            $requestData['client_uri'] ?? null,
            $requestData['logo_uri'] ?? null,
            $requestData['tos_uri'] ?? null,
            $requestData['policy_uri'] ?? null,
            $requestData['software_id'] ?? null,
            $requestData['software_version'] ?? null
        );

        // クライアントを保存
        $this->clientRepository->persistNewClient($client);

        return $client;
    }

    /**
     * クライアント情報の取得
     *
     * @param string $clientId クライアントID
     * @param string $registrationAccessToken 登録アクセストークン
     * @return OAuth2Client|null
     */
    public function getClient(string $clientId, string $registrationAccessToken): ?OAuth2Client
    {
        $client = $this->clientRepository->getClientEntityWithExtensions($clientId);

        if (!$client || $client->getRegistrationAccessToken() !== $registrationAccessToken) {
            return null;
        }

        return $client;
    }

    /**
     * クライアント情報の更新
     *
     * @param string $clientId クライアントID
     * @param string $registrationAccessToken 登録アクセストークン
     * @param array $requestData 更新データ
     * @return OAuth2Client|null
     * @throws Exception
     */
    public function updateClient(string $clientId, string $registrationAccessToken, array $requestData): ?OAuth2Client
    {
        $client = $this->getClient($clientId, $registrationAccessToken);

        if (!$client) {
            return null;
        }

        // リクエストデータの検証
        $this->validateRegistrationRequest($requestData);

        // 更新されたクライアントオブジェクトを作成
        $updatedClient = new OAuth2Client(
            $clientId,
            $requestData['client_name'] ?? $client->getName(),
            $requestData['redirect_uris'] ?? $client->getRedirectUri(),
            $client->getSecret(), // シークレットは変更しない
            $requestData['grant_types'] ?? $client->getGrants(),
            $this->parseScopes($requestData['scope'] ?? implode(' ', $client->getScopes())),
            $client->getRegistrationAccessToken(),
            $client->getRegistrationClientUri(),
            $client->getClientIdIssuedAt(),
            $client->getClientSecretExpiresAt(),
            $requestData['token_endpoint_auth_method'] ?? $client->getTokenEndpointAuthMethod(),
            $requestData['contacts'] ?? $client->getContacts(),
            $requestData['client_uri'] ?? $client->getClientUri(),
            $requestData['logo_uri'] ?? $client->getLogoUri(),
            $requestData['tos_uri'] ?? $client->getTosUri(),
            $requestData['policy_uri'] ?? $client->getPolicyUri(),
            $requestData['software_id'] ?? $client->getSoftwareId(),
            $requestData['software_version'] ?? $client->getSoftwareVersion()
        );

        // クライアントを更新
        $this->clientRepository->updateClient($updatedClient);

        return $updatedClient;
    }

    /**
     * クライアントの削除
     *
     * @param string $clientId クライアントID
     * @param string $registrationAccessToken 登録アクセストークン
     * @return bool
     */
    public function deleteClient(string $clientId, string $registrationAccessToken): bool
    {
        $client = $this->getClient($clientId, $registrationAccessToken);

        if (!$client) {
            return false;
        }

        return $this->clientRepository->deleteClient($clientId);
    }

    /**
     * 登録リクエストの検証
     *
     * @param array $requestData
     * @throws Exception
     */
    private function validateRegistrationRequest(array $requestData): void
    {
        // リダイレクトURIの検証
        if (isset($requestData['redirect_uris'])) {
            if (!is_array($requestData['redirect_uris'])) {
                throw new Exception('redirect_uris must be an array');
            }

            foreach ($requestData['redirect_uris'] as $uri) {
                if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid redirect_uri: ' . $uri);
                }
            }
        }

        // グラントタイプの検証
        if (isset($requestData['grant_types'])) {
            if (!is_array($requestData['grant_types'])) {
                throw new Exception('grant_types must be an array');
            }

            foreach ($requestData['grant_types'] as $grantType) {
                if (!in_array($grantType, $this->supportedGrantTypes)) {
                    throw new Exception('Unsupported grant_type: ' . $grantType);
                }
            }
        }

        // レスポンスタイプの検証
        if (isset($requestData['response_types'])) {
            if (!is_array($requestData['response_types'])) {
                throw new Exception('response_types must be an array');
            }

            foreach ($requestData['response_types'] as $responseType) {
                if (!in_array($responseType, $this->supportedResponseTypes)) {
                    throw new Exception('Unsupported response_type: ' . $responseType);
                }
            }
        }

        // トークンエンドポイント認証方法の検証
        if (isset($requestData['token_endpoint_auth_method'])) {
            if (!in_array($requestData['token_endpoint_auth_method'], $this->supportedAuthMethods)) {
                throw new Exception('Unsupported token_endpoint_auth_method: ' . $requestData['token_endpoint_auth_method']);
            }
        }

        // スコープの検証
        if (isset($requestData['scope'])) {
            $scopes = $this->parseScopes($requestData['scope']);
            foreach ($scopes as $scope) {
                if (!in_array($scope, $this->supportedScopes)) {
                    throw new Exception('Unsupported scope: ' . $scope);
                }
            }
        }
    }

    /**
     * スコープ文字列をパース
     *
     * @param string $scopeString
     * @return array
     */
    private function parseScopes(string $scopeString): array
    {
        if (empty($scopeString)) {
            return [];
        }

        return array_filter(explode(' ', $scopeString));
    }

    /**
     * クライアントIDを生成
     *
     * @return string
     */
    private function generateClientId(): string
    {
        return 'client_' . bin2hex(random_bytes(16));
    }

    /**
     * クライアントシークレットを生成
     *
     * @return string
     */
    private function generateClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 登録アクセストークンを生成
     *
     * @return string
     */
    private function generateRegistrationAccessToken(): string
    {
        return 'reg_' . bin2hex(random_bytes(32));
    }
}
