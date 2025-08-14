<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 Client Entity (League OAuth2 Server用)
 */
class OAuth2ClientEntity implements ClientEntityInterface
{
    use EntityTrait, ClientTrait;

    /**
     * クライアント名
     *
     * @var string
     */
    protected $name;

    public function __construct()
    {
        $this->isConfidential = true;
    }

    /**
     * クライアント名を設定
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * クライアント名を取得
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * リダイレクトURIを設定
     *
     * @param array $uri
     * @return void
     */
    public function setRedirectUri(array $uri): void
    {
        $this->redirectUri = $uri;
    }

    /**
     * リダイレクトURIを取得
     *
     * @return array
     */
    public function getRedirectUri(): array
    {
        return $this->redirectUri;
    }

    /**
     * 機密クライアントかどうかを設定
     *
     * @param bool $isConfidential
     * @return void
     */
    public function setIsConfidential(bool $isConfidential): void
    {
        $this->isConfidential = $isConfidential;
    }

    /**
     * 機密クライアントかどうかを判定
     *
     * @return bool
     */
    public function isConfidential(): bool
    {
        return $this->isConfidential;
    }
}
