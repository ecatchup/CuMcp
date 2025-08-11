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
     * 設定されたクライアント情報
     *
     * @var array
     */
    private array $clients;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // デフォルトのクライアント情報を設定
        $this->clients = [
            'mcp-client' => [
                'name' => 'MCP Server Client',
                'secret' => 'mcp-secret-key',
                'redirect_uris' => ['http://localhost'],
                'grants' => ['client_credentials'],
                'scopes' => ['read', 'write']
            ]
        ];
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
        if (!isset($this->clients[$clientIdentifier])) {
            return null;
        }

        $clientData = $this->clients[$clientIdentifier];

        // グラントタイプの検証
        if ($grantType && !in_array($grantType, $clientData['grants'] ?? [])) {
            return null;
        }

        return new OAuth2Client(
            $clientIdentifier,
            $clientData['name'],
            $clientData['redirect_uris'] ?? [],
            $clientData['secret'] ?? null,
            $clientData['grants'] ?? [],
            $clientData['scopes'] ?? []
        );
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
        if (empty($this->clients[$clientIdentifier]['secret'])) {
            return true;
        }

        // 機密クライアントの場合は秘密キーを検証
        return hash_equals($this->clients[$clientIdentifier]['secret'], $clientSecret ?? '');
    }
}
