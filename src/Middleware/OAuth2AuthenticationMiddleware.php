<?php
declare(strict_types=1);

namespace CuMcp\Middleware;

use CuMcp\Service\OAuth2Service;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Http\Response;

/**
 * OAuth2認証ミドルウェア
 *
 * MCPサーバーへのアクセスにOAuth2認証を要求
 */
class OAuth2AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * OAuth2サービス
     *
     * @var OAuth2Service
     */
    private OAuth2Service $oauth2Service;

    /**
     * 認証が不要なパス
     *
     * @var array
     */
    private array $publicPaths = [
        '/cu-mcp/oauth2/token',
        '/cu-mcp/oauth2/options'
    ];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->oauth2Service = new OAuth2Service();
    }

    /**
     * ミドルウェア処理
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // 公開パスは認証をスキップ
        if ($this->isPublicPath($path)) {
            return $handler->handle($request);
        }

        // CuMcpプラグインのパスでない場合はスキップ
        if (!str_starts_with($path, '/cu-mcp/')) {
            return $handler->handle($request);
        }

        // Authorizationヘッダーをチェック
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->createUnauthorizedResponse('Missing or invalid authorization header');
        }

        // トークンを抽出して検証
        $token = substr($authHeader, 7);
        $tokenData = $this->oauth2Service->validateAccessToken($token);

        if (!$tokenData) {
            return $this->createUnauthorizedResponse('Invalid or expired access token');
        }

        // トークン情報をリクエストに追加
        $request = $request
            ->withAttribute('oauth_client_id', $tokenData['client_id'])
            ->withAttribute('oauth_user_id', $tokenData['user_id'])
            ->withAttribute('oauth_scopes', $tokenData['scopes']);

        return $handler->handle($request);
    }

    /**
     * 公開パスかどうかチェック
     *
     * @param string $path
     * @return bool
     */
    private function isPublicPath(string $path): bool
    {
        foreach ($this->publicPaths as $publicPath) {
            if (str_starts_with($path, $publicPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 未認証レスポンスを作成
     *
     * @param string $message
     * @return ResponseInterface
     */
    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        return $response
            ->withStatus(401)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'error' => 'unauthorized',
                'error_description' => $message
            ]));
    }
}
