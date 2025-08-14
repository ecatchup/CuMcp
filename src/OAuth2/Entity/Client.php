<?php
declare(strict_types=1);

namespace CuMcp\OAuth2\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 Client (Protocol layer)
 */
class Client implements ClientEntityInterface
{
    use EntityTrait, ClientTrait;

    protected $name;

    public function __construct()
    {
        $this->isConfidential = true;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @param array<string> $uri */
    public function setRedirectUri(array $uri): void
    {
        $this->redirectUri = $uri;
    }

    /** @return array<string> */
    public function getRedirectUri(): array
    {
        return $this->redirectUri;
    }

    public function setIsConfidential(bool $isConfidential): void
    {
        $this->isConfidential = $isConfidential;
    }

    public function isConfidential(): bool
    {
        return $this->isConfidential;
    }
}
