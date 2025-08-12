<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * OAuth2 Authorization Code Entity
 */
class OAuth2AuthCode implements AuthCodeEntityInterface
{
    use AuthCodeTrait, EntityTrait, TokenEntityTrait;

    /**
     * リダイレクトURI
     *
     * @var string
     */
    protected string $redirectUri;

    /**
     * リダイレクトURIを取得
     *
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * リダイレクトURIを設定
     *
     * @param string $uri
     * @return void
     */
    public function setRedirectUri(string $uri): void
    {
        $this->redirectUri = $uri;
    }
}
