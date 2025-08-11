<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * OAuth2 Access Token Entity
 */
class OAuth2AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use EntityTrait;
    use TokenEntityTrait;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // 初期化処理
    }

    /**
     * クライアントエンティティを設定
     *
     * @param ClientEntityInterface $client
     * @return void
     */
    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * スコープを追加
     *
     * @param ScopeEntityInterface $scope
     * @return void
     */
    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes[$scope->getIdentifier()] = $scope;
    }

    /**
     * ユーザー識別子を設定
     *
     * @param string|int|null $identifier
     * @return void
     */
    public function setUserIdentifier($identifier): void
    {
        $this->userIdentifier = $identifier;
    }
}
