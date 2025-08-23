<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Entity;

use CuMcp\OAuth2\Entity\Trait\Rfc9068AccessTokenTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * OAuth2 Access Token (Protocol layer)
 * RFC 9068 準拠のアクセストークンを生成
 */
class AccessToken implements AccessTokenEntityInterface
{
    use Rfc9068AccessTokenTrait;
    use EntityTrait;
    use TokenEntityTrait;

    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes[$scope->getIdentifier()] = $scope;
    }

    /**
     * @param string|int|null $identifier
     */
    public function setUserIdentifier($identifier): void
    {
        $this->userIdentifier = $identifier;
    }
}
