<?php
declare(strict_types=1);

namespace CuMcp\Model\Repository;

use CuMcp\Model\Entity\OAuth2Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * OAuth2 Client Repository
 */
class OAuth2ClientRepository implements ClientRepositoryInterface
{
    /**
     * 設定されたクライアント情報（静的保持）
     *
     * @var array
     */
    private static array $clients = [];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // 初期化時にデフォルトクライアントが存在しない場合のみ追加
        if (empty(self::$clients)) {
            self::$clients = [
                'mcp-client' => [
                    'name' => 'MCP Server Client',
                    'secret' => 'mcp-secret-key',
                    'redirect_uris' => ['http://localhost'],
                    'grants' => ['client_credentials'],
                    'scopes' => ['read', 'write']
                ]
            ];
        }
    }

    /**
     * クライアントエンティティを取得
     *
     * @param string $clientIdentifier クライアントID
     * @param string|null $grantType グラントタイプ
     * @param string|null $clientSecret クライアント秘密キー
     * @param bool $mustValidateSecret 秘密キーの検証が必要かどうか
     * @return ClientEntityInterface|null
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true): ?ClientEntityInterface
    {
        if (!isset(self::$clients[$clientIdentifier])) {
            return null;
        }

        $clientData = self::$clients[$clientIdentifier];

        // グラントタイプの検証
        if ($grantType && !in_array($grantType, $clientData['grants'] ?? [])) {
            return null;
        }

        return $this->getClientEntityWithExtensions($clientIdentifier);
    }

    /**
     * クライアント認証
     *
     * @param string $clientIdentifier クライアントID
     * @param string|null $clientSecret クライアント秘密キー
     * @param string|null $grantType グラントタイプ
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $client = $this->getClientEntity($clientIdentifier, $grantType, null, false);

        if (!$client) {
            return false;
        }

        // パブリッククライアント（秘密キーなし）の場合は認証成功
        if (empty(self::$clients[$clientIdentifier]['secret'])) {
            return true;
        }

        // 機密クライアントの場合は秘密キーを検証
        return hash_equals(self::$clients[$clientIdentifier]['secret'], $clientSecret ?? '');
    }

    /**
     * 新しいクライアントを永続化
     *
     * @param OAuth2Client $client
     * @return bool
     */
    public function persistNewClient(OAuth2Client $client): bool
    {
        self::$clients[$client->getIdentifier()] = [
            'name' => $client->getName(),
            'secret' => $client->getSecret(),
            'redirect_uris' => $client->getRedirectUri(),
            'grants' => $client->getGrants(),
            'scopes' => $client->getScopes(),
            'registration_access_token' => $client->getRegistrationAccessToken(),
            'registration_client_uri' => $client->getRegistrationClientUri(),
            'client_id_issued_at' => $client->getClientIdIssuedAt(),
            'client_secret_expires_at' => $client->getClientSecretExpiresAt(),
            'token_endpoint_auth_method' => $client->getTokenEndpointAuthMethod(),
            'contacts' => $client->getContacts(),
            'client_uri' => $client->getClientUri(),
            'logo_uri' => $client->getLogoUri(),
            'tos_uri' => $client->getTosUri(),
            'policy_uri' => $client->getPolicyUri(),
            'software_id' => $client->getSoftwareId(),
            'software_version' => $client->getSoftwareVersion()
        ];

        return true;
    }

    /**
     * クライアント情報を更新
     *
     * @param OAuth2Client $client
     * @return bool
     */
    public function updateClient(OAuth2Client $client): bool
    {
        if (!isset(self::$clients[$client->getIdentifier()])) {
            return false;
        }

        return $this->persistNewClient($client);
    }

    /**
     * クライアントを削除
     *
     * @param string $clientIdentifier
     * @return bool
     */
    public function deleteClient(string $clientIdentifier): bool
    {
        if (!isset(self::$clients[$clientIdentifier])) {
            return false;
        }

        unset(self::$clients[$clientIdentifier]);
        return true;
    }

    /**
     * 拡張フィールドを含むクライアントエンティティを取得
     *
     * @param string $clientIdentifier
     * @return OAuth2Client|null
     */
    public function getClientEntityWithExtensions(string $clientIdentifier): ?OAuth2Client
    {
        if (!isset(self::$clients[$clientIdentifier])) {
            return null;
        }

        $clientData = self::$clients[$clientIdentifier];

        return new OAuth2Client(
            $clientIdentifier,
            $clientData['name'],
            $clientData['redirect_uris'] ?? [],
            $clientData['secret'] ?? null,
            $clientData['grants'] ?? [],
            $clientData['scopes'] ?? [],
            $clientData['registration_access_token'] ?? null,
            $clientData['registration_client_uri'] ?? null,
            $clientData['client_id_issued_at'] ?? null,
            $clientData['client_secret_expires_at'] ?? null,
            $clientData['token_endpoint_auth_method'] ?? 'client_secret_basic',
            $clientData['contacts'] ?? [],
            $clientData['client_uri'] ?? null,
            $clientData['logo_uri'] ?? null,
            $clientData['tos_uri'] ?? null,
            $clientData['policy_uri'] ?? null,
            $clientData['software_id'] ?? null,
            $clientData['software_version'] ?? null
        );
    }
}
