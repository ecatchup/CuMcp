<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 Client Entity
 */
class OAuth2Client implements ClientEntityInterface
{
    use EntityTrait;

    /**
     * クライアント名
     *
     * @var string
     */
    private string $name;

    /**
     * リダイレクトURI
     *
     * @var array
     */
    private array $redirectUris;

    /**
     * 秘密キー
     *
     * @var string|null
     */
    private ?string $secret;

    /**
     * 許可されたグラント
     *
     * @var array
     */
    private array $grants;

    /**
     * 許可されたスコープ
     *
     * @var array
     */
    private array $scopes;

    /**
     * コンストラクタ
     *
     * @param string $identifier クライアントID
     * @param string $name クライアント名
     * @param array $redirectUris リダイレクトURI
     * @param string|null $secret 秘密キー
     * @param array $grants 許可されたグラント
     * @param array $scopes 許可されたスコープ
     */
    public function __construct(
        string $identifier,
        string $name,
        array $redirectUris = [],
        ?string $secret = null,
        array $grants = [],
        array $scopes = []
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUris = $redirectUris;
        $this->secret = $secret;
        $this->grants = $grants;
        $this->scopes = $scopes;
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
     * リダイレクトURIを取得
     *
     * @return array
     */
    public function getRedirectUri(): array
    {
        return $this->redirectUris;
    }

    /**
     * 秘密キーを取得
     *
     * @return string|null
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * 機密クライアントかどうか
     *
     * @return bool
     */
    public function isConfidential(): bool
    {
        // パブリッククライアント（秘密キーがnullまたは空文字列）の場合はfalse
        return !empty($this->secret);
    }

    /**
     * 許可されたグラントを取得
     *
     * @return array
     */
    public function getGrants(): array
    {
        return $this->grants;
    }

    /**
     * 許可されたスコープを取得
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
