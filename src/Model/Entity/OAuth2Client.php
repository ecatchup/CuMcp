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
     * クライアント登録アクセストークン
     *
     * @var string|null
     */
    private ?string $registrationAccessToken;

    /**
     * クライアント設定URI
     *
     * @var string|null
     */
    private ?string $registrationClientUri;

    /**
     * クライアント作成日時
     *
     * @var int|null
     */
    private ?int $clientIdIssuedAt;

    /**
     * クライアント秘密キー有効期限
     *
     * @var int|null
     */
    private ?int $clientSecretExpiresAt;

    /**
     * トークンエンドポイント認証方法
     *
     * @var string
     */
    private string $tokenEndpointAuthMethod;

    /**
     * 連絡先メールアドレス
     *
     * @var array
     */
    private array $contacts;

    /**
     * クライアントURI
     *
     * @var string|null
     */
    private ?string $clientUri;

    /**
     * ロゴURI
     *
     * @var string|null
     */
    private ?string $logoUri;

    /**
     * 利用規約URI
     *
     * @var string|null
     */
    private ?string $tosUri;

    /**
     * プライバシーポリシーURI
     *
     * @var string|null
     */
    private ?string $policyUri;

    /**
     * ソフトウェアID
     *
     * @var string|null
     */
    private ?string $softwareId;

    /**
     * ソフトウェアバージョン
     *
     * @var string|null
     */
    private ?string $softwareVersion;

    /**
     * コンストラクタ
     *
     * @param string $identifier クライアントID
     * @param string $name クライアント名
     * @param array $redirectUris リダイレクトURI
     * @param string|null $secret 秘密キー
     * @param array $grants 許可されたグラント
     * @param array $scopes 許可されたスコープ
     * @param string|null $registrationAccessToken 登録アクセストークン
     * @param string|null $registrationClientUri クライアント設定URI
     * @param int|null $clientIdIssuedAt クライアント作成日時
     * @param int|null $clientSecretExpiresAt クライアント秘密キー有効期限
     * @param string $tokenEndpointAuthMethod トークンエンドポイント認証方法
     * @param array $contacts 連絡先
     * @param string|null $clientUri クライアントURI
     * @param string|null $logoUri ロゴURI
     * @param string|null $tosUri 利用規約URI
     * @param string|null $policyUri プライバシーポリシーURI
     * @param string|null $softwareId ソフトウェアID
     * @param string|null $softwareVersion ソフトウェアバージョン
     */
    public function __construct(
        string $identifier,
        string $name,
        array $redirectUris = [],
        ?string $secret = null,
        array $grants = [],
        array $scopes = [],
        ?string $registrationAccessToken = null,
        ?string $registrationClientUri = null,
        ?int $clientIdIssuedAt = null,
        ?int $clientSecretExpiresAt = null,
        string $tokenEndpointAuthMethod = 'client_secret_basic',
        array $contacts = [],
        ?string $clientUri = null,
        ?string $logoUri = null,
        ?string $tosUri = null,
        ?string $policyUri = null,
        ?string $softwareId = null,
        ?string $softwareVersion = null
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUris = $redirectUris;
        $this->secret = $secret;
        $this->grants = $grants;
        $this->scopes = $scopes;
        $this->registrationAccessToken = $registrationAccessToken;
        $this->registrationClientUri = $registrationClientUri;
        $this->clientIdIssuedAt = $clientIdIssuedAt;
        $this->clientSecretExpiresAt = $clientSecretExpiresAt;
        $this->tokenEndpointAuthMethod = $tokenEndpointAuthMethod;
        $this->contacts = $contacts;
        $this->clientUri = $clientUri;
        $this->logoUri = $logoUri;
        $this->tosUri = $tosUri;
        $this->policyUri = $policyUri;
        $this->softwareId = $softwareId;
        $this->softwareVersion = $softwareVersion;
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

    /**
     * 登録アクセストークンを取得
     *
     * @return string|null
     */
    public function getRegistrationAccessToken(): ?string
    {
        return $this->registrationAccessToken;
    }

    /**
     * クライアント設定URIを取得
     *
     * @return string|null
     */
    public function getRegistrationClientUri(): ?string
    {
        return $this->registrationClientUri;
    }

    /**
     * クライアント作成日時を取得
     *
     * @return int|null
     */
    public function getClientIdIssuedAt(): ?int
    {
        return $this->clientIdIssuedAt;
    }

    /**
     * クライアント秘密キー有効期限を取得
     *
     * @return int|null
     */
    public function getClientSecretExpiresAt(): ?int
    {
        return $this->clientSecretExpiresAt;
    }

    /**
     * トークンエンドポイント認証方法を取得
     *
     * @return string
     */
    public function getTokenEndpointAuthMethod(): string
    {
        return $this->tokenEndpointAuthMethod;
    }

    /**
     * 連絡先を取得
     *
     * @return array
     */
    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * クライアントURIを取得
     *
     * @return string|null
     */
    public function getClientUri(): ?string
    {
        return $this->clientUri;
    }

    /**
     * ロゴURIを取得
     *
     * @return string|null
     */
    public function getLogoUri(): ?string
    {
        return $this->logoUri;
    }

    /**
     * 利用規約URIを取得
     *
     * @return string|null
     */
    public function getTosUri(): ?string
    {
        return $this->tosUri;
    }

    /**
     * プライバシーポリシーURIを取得
     *
     * @return string|null
     */
    public function getPolicyUri(): ?string
    {
        return $this->policyUri;
    }

    /**
     * ソフトウェアIDを取得
     *
     * @return string|null
     */
    public function getSoftwareId(): ?string
    {
        return $this->softwareId;
    }

    /**
     * ソフトウェアバージョンを取得
     *
     * @return string|null
     */
    public function getSoftwareVersion(): ?string
    {
        return $this->softwareVersion;
    }

    /**
     * RFC7591準拠のクライアント情報配列を取得
     *
     * @return array
     */
    public function toRegistrationResponse(): array
    {
        $response = [
            'client_id' => $this->getIdentifier(),
            'client_name' => $this->getName(),
            'redirect_uris' => $this->getRedirectUri(),
            'grant_types' => $this->getGrants(),
            'scope' => implode(' ', $this->getScopes()),
            'token_endpoint_auth_method' => $this->getTokenEndpointAuthMethod(),
        ];

        if ($this->getSecret()) {
            $response['client_secret'] = $this->getSecret();
        }

        if ($this->getRegistrationAccessToken()) {
            $response['registration_access_token'] = $this->getRegistrationAccessToken();
        }

        if ($this->getRegistrationClientUri()) {
            $response['registration_client_uri'] = $this->getRegistrationClientUri();
        }

        if ($this->getClientIdIssuedAt()) {
            $response['client_id_issued_at'] = $this->getClientIdIssuedAt();
        }

        if ($this->getClientSecretExpiresAt()) {
            $response['client_secret_expires_at'] = $this->getClientSecretExpiresAt();
        }

        if (!empty($this->getContacts())) {
            $response['contacts'] = $this->getContacts();
        }

        if ($this->getClientUri()) {
            $response['client_uri'] = $this->getClientUri();
        }

        if ($this->getLogoUri()) {
            $response['logo_uri'] = $this->getLogoUri();
        }

        if ($this->getTosUri()) {
            $response['tos_uri'] = $this->getTosUri();
        }

        if ($this->getPolicyUri()) {
            $response['policy_uri'] = $this->getPolicyUri();
        }

        if ($this->getSoftwareId()) {
            $response['software_id'] = $this->getSoftwareId();
        }

        if ($this->getSoftwareVersion()) {
            $response['software_version'] = $this->getSoftwareVersion();
        }

        return $response;
    }
}
