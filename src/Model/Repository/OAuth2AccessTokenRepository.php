<?php
declare(strict_types=1);

namespace CuMcp\Model\Repository;

use CuMcp\OAuth2\Entity\AccessToken as OAuth2AccessToken;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;

/**
 * OAuth2 Access Token Repository
 */
class OAuth2AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * シングルトンインスタンス
     *
     * @var OAuth2AccessTokenRepository|null
     */
    private static ?OAuth2AccessTokenRepository $instance = null;

    /**
     * 永続化されたアクセストークン
     *
     * @var array
     */
    private static array $persistedTokens = [];

    /**
     * シングルトンインスタンスを取得
     *
     * @return OAuth2AccessTokenRepository
     */
    public static function getInstance(): OAuth2AccessTokenRepository
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 新しいアクセストークンを取得
     *
     * @param ClientEntityInterface $clientEntity
     * @param array $scopes
     * @param string|int|null $userIdentifier
     * @return AccessTokenEntityInterface
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new OAuth2AccessToken();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    /**
     * アクセストークンを永続化
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     * @return void
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $identifier = $accessTokenEntity->getIdentifier();
        
        if (isset(self::$persistedTokens[$identifier])) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        self::$persistedTokens[$identifier] = [
            'identifier' => $identifier,
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => array_keys($accessTokenEntity->getScopes()),
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
            'revoked' => false
        ];
    }

    /**
     * アクセストークンを取り消し
     *
     * @param string $tokenId
     * @return void
     */
    public function revokeAccessToken($tokenId): void
    {
        if (isset(self::$persistedTokens[$tokenId])) {
            self::$persistedTokens[$tokenId]['revoked'] = true;
        }
    }

    /**
     * アクセストークンが取り消されているかチェック
     *
     * @param string $tokenId
     * @return bool
     */
    public function isAccessTokenRevoked($tokenId): bool
    {
        if (!isset(self::$persistedTokens[$tokenId])) {
            return true;
        }

        return self::$persistedTokens[$tokenId]['revoked'];
    }

    /**
     * アクセストークンのデータを取得（検証用）
     *
     * @param string $tokenId
     * @return array|null
     */
    public function getAccessTokenData(string $tokenId): ?array
    {
        if (!isset(self::$persistedTokens[$tokenId])) {
            return null;
        }

        if (self::$persistedTokens[$tokenId]['revoked']) {
            return null;
        }

        return self::$persistedTokens[$tokenId];
    }
}
