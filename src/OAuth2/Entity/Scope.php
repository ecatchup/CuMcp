<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 Scope (Protocol layer)
 */
class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    private string $description;

    public function __construct(string $identifier, string $description = '')
    {
        $this->identifier = $identifier;
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}
