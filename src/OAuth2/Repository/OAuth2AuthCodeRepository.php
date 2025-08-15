<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Repository;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use CuMcp\OAuth2\Entity\AuthCode as OAuth2AuthCode;

/**
 * OAuth2 Authorization Code Repository
 *
 * 認可コードの管理を行う
 */
class OAuth2AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * 認可コードの一時保存用
     *
     * @var array
     */
    private static array $authCodes = [];

    /**
     * 新しい認可コードエンティティを作成
     *
     * @return AuthCodeEntityInterface
     */
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new OAuth2AuthCode();
    }

    /**
     * 認可コードを永続化
     *
     * @param AuthCodeEntityInterface $authCodeEntity
     * @return void
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        // 実際のプロダクション環境ではデータベースに保存
        // ここでは一時的にメモリに保存
        self::$authCodes[$authCodeEntity->getIdentifier()] = [
            'code' => $authCodeEntity->getIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'scopes' => array_map(fn($scope) => $scope->getIdentifier(), $authCodeEntity->getScopes()),
            'expires_at' => $authCodeEntity->getExpiryDateTime()->getTimestamp(),
            'redirect_uri' => $authCodeEntity->getRedirectUri(),
            'revoked' => false
        ];
    }

    /**
     * 認可コードを無効化
     *
     * @param string $codeId
     * @return void
     */
    public function revokeAuthCode($codeId): void
    {
        if (isset(self::$authCodes[$codeId])) {
            self::$authCodes[$codeId]['revoked'] = true;
        }
    }

    /**
     * 認可コードが無効化されているかチェック
     *
     * @param string $codeId
     * @return bool
     */
    public function isAuthCodeRevoked($codeId): bool
    {
        return self::$authCodes[$codeId]['revoked'] ?? true;
    }

    /**
     * 認可コードを保存（OAuth2Controller から呼び出される）
     *
     * @param array $data
     * @return void
     */
    public function storeAuthorizationCode(array $data): void
    {
        self::$authCodes[$data['code']] = $data + ['revoked' => false];
    }

    /**
     * 認可コードを取得
     *
     * @param string $code
     * @return array|null
     */
    public function getAuthorizationCode(string $code): ?array
    {
        return self::$authCodes[$code] ?? null;
    }
}
