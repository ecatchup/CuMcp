<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Repository;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use CuMcp\OAuth2\Entity\RefreshToken as OAuth2RefreshToken;

/**
 * OAuth2 Refresh Token Repository
 *
 * リフレッシュトークンの管理を行う
 */
class OAuth2RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * リフレッシュトークンの一時保存用
     *
     * @var array
     */
    private static array $refreshTokens = [];

    /**
     * 新しいリフレッシュトークンエンティティを作成
     *
     * @return RefreshTokenEntityInterface
     */
    public function getNewRefreshToken(): RefreshTokenEntityInterface
    {
        return new OAuth2RefreshToken();
    }

    /**
     * リフレッシュトークンを永続化
     *
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     * @return void
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        // 実際のプロダクション環境ではデータベースに保存
        // ここでは一時的にメモリに保存
        self::$refreshTokens[$refreshTokenEntity->getIdentifier()] = [
            'token' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'expires_at' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
            'revoked' => false
        ];
    }

    /**
     * リフレッシュトークンを無効化
     *
     * @param string $tokenId
     * @return void
     */
    public function revokeRefreshToken($tokenId): void
    {
        if (isset(self::$refreshTokens[$tokenId])) {
            self::$refreshTokens[$tokenId]['revoked'] = true;
        }
    }

    /**
     * リフレッシュトークンが無効化されているかチェック
     *
     * @param string $tokenId
     * @return bool
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        return self::$refreshTokens[$tokenId]['revoked'] ?? true;
    }
}
