<?php
declare(strict_types=1);

namespace CuMcp\Model\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use CuMcp\Model\Entity\OAuth2User;

/**
 * OAuth2 User Repository
 *
 * ユーザー情報の管理を行う
 */
class OAuth2UserRepository implements UserRepositoryInterface
{
    /**
     * ユーザー認証情報でユーザーエンティティを取得
     *
     * @param string $username
     * @param string $password
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @return UserEntityInterface|null
     */
    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        // Authorization Code Grant では直接的なユーザー認証は行わない
        // 認可エンドポイントで既にログイン済みのユーザー情報を使用
        return null;
    }
}
