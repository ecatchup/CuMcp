<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 Scope Entity
 */
class OAuth2Scope implements ScopeEntityInterface
{
    use EntityTrait;

    /**
     * スコープの説明
     *
     * @var string
     */
    private string $description;

    /**
     * コンストラクタ
     *
     * @param string $identifier スコープ識別子
     * @param string $description スコープの説明
     */
    public function __construct(string $identifier, string $description = '')
    {
        $this->identifier = $identifier;
        $this->description = $description;
    }

    /**
     * スコープの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * JSON シリアライゼーション
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}
