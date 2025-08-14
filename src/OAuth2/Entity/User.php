<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * OAuth2 User (Protocol layer)
 */
class User implements UserEntityInterface
{
    protected string|int $identifier;

    public function getIdentifier(): string|int
    {
        return $this->identifier;
    }

    public function setIdentifier(string|int $identifier): void
    {
        $this->identifier = $identifier;
    }
}
