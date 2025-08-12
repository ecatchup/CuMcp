<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * OAuth2 User Entity
 */
class OAuth2User implements UserEntityInterface
{
    /**
     * ユーザーID
     *
     * @var string|int
     */
    protected string|int $identifier;

    /**
     * ユーザーIDを取得
     *
     * @return string|int
     */
    public function getIdentifier(): string|int
    {
        return $this->identifier;
    }

    /**
     * ユーザーIDを設定
     *
     * @param string|int $identifier
     * @return void
     */
    public function setIdentifier(string|int $identifier): void
    {
        $this->identifier = $identifier;
    }
}
