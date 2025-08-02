<?php
declare(strict_types=1);
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.7
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace CuMcp\Controller\Api\Admin;

use BaserCore\Controller\Api\Admin\BcAdminApiController;
use CuMcp\Service\McpToolWrapperService;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;

/**
 * MCP HTTP API コントローラー
 * MCPサーバーの機能をHTTP APIとして提供
 */
class McpController extends BcAdminApiController
{
    private McpToolWrapperService $mcpService;

    public function initialize(): void
    {
        parent::initialize();
        $this->mcpService = new McpToolWrapperService();
    }

    /**
     * ブログ記事追加
     * POST /baser/api/admin/cu-mcp/mcp/add-blog-post
     */
    public function addBlogPost()
    {
        $this->request->allowMethod(['post']);

        $data = $this->request->getData();

        try {
            $result = $this->mcpService->addBlogPost($data);

            if (!empty($result['error'])) {
                throw new BadRequestException($result['message'] ?? 'ブログ記事の追加に失敗しました');
            }

            $this->set([
                'blogPost' => $result['data'] ?? $result,
                'message' => 'ブログ記事を正常に追加しました',
                'success' => true
            ]);
            $this->viewBuilder()->setOption('serialize', ['blogPost', 'message', 'success']);

        } catch (\InvalidArgumentException $e) {
            throw new BadRequestException($e->getMessage());
        } catch (\Exception $e) {
            throw new InternalErrorException('サーバーエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * MCPツール情報取得
     * GET /baser/api/admin/cu-mcp/mcp/tool-info
     */
    public function toolInfo()
    {
        $this->request->allowMethod(['get']);

        $toolInfo = $this->mcpService->getToolInfo();

        $this->set([
            'tool' => $toolInfo,
            'endpoint' => '/baser/api/admin/cu-mcp/mcp/add-blog-post',
            'method' => 'POST',
            'example' => [
                'title' => 'サンプル記事タイトル',
                'detail' => 'サンプル記事の詳細内容です。',
                'category' => 'お知らせ',
                'blog_content' => 'news',
                'email' => 'admin@example.com'
            ]
        ]);
        $this->viewBuilder()->setOption('serialize', ['tool', 'endpoint', 'method', 'example']);
    }

    /**
     * MCPサーバー情報取得
     * GET /baser/api/admin/cu-mcp/mcp/server-info
     */
    public function serverInfo()
    {
        $this->request->allowMethod(['get']);

        $this->set([
            'server' => [
                'name' => 'baserCMS MCP Server',
                'version' => '1.0.0',
                'description' => 'baserCMSのMCP機能をHTTP APIとして提供',
                'type' => 'HTTP API Endpoint'
            ],
            'available_tools' => [
                'addBlogPost' => 'ブログ記事追加'
            ],
            'base_url' => '/baser/api/admin/cu-mcp/mcp'
        ]);
        $this->viewBuilder()->setOption('serialize', ['server', 'available_tools', 'base_url']);
    }

    /**
     * SSEストリーム（ブログ記事追加の進行状況をリアルタイム送信）
     * GET /baser/api/admin/cu-mcp/mcp/stream-add-blog-post
     */
    public function streamAddBlogPost()
    {
        $this->request->allowMethod(['get']);

        // SSEヘッダーを設定
        $this->response = $this->response
            ->withType('text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization');

        // 出力バッファリングを無効化
        if (ob_get_level()) {
            ob_end_clean();
        }

        // リクエストパラメータを取得
        $title = $this->request->getQuery('title');
        $detail = $this->request->getQuery('detail');
        $category = $this->request->getQuery('category');

        if (!$title || !$detail) {
            $this->sendSseEvent('error', ['message' => 'titleとdetailパラメータが必要です']);
            return $this->response;
        }

        // 開始イベントを送信
        $this->sendSseEvent('start', ['message' => 'ブログ記事追加を開始します']);

        try {
            // 進行状況を段階的に送信
            $this->sendSseEvent('progress', ['step' => 1, 'message' => 'データ検証中...', 'progress' => 25]);
            usleep(500000); // 0.5秒待機

            $this->sendSseEvent('progress', ['step' => 2, 'message' => 'カテゴリ確認中...', 'progress' => 50]);
            usleep(500000);

            $this->sendSseEvent('progress', ['step' => 3, 'message' => 'ブログ記事作成中...', 'progress' => 75]);
            usleep(500000);

            // 実際のブログ記事追加処理
            $data = [
                'title' => $title,
                'detail' => $detail,
                'category' => $category
            ];

            $result = $this->mcpService->addBlogPost($data);

            if (!empty($result['error'])) {
                $this->sendSseEvent('error', [
                    'message' => $result['message'] ?? 'ブログ記事の追加に失敗しました',
                    'error' => $result
                ]);
            } else {
                $this->sendSseEvent('progress', ['step' => 4, 'message' => '完了', 'progress' => 100]);
                $this->sendSseEvent('success', [
                    'message' => 'ブログ記事を正常に追加しました',
                    'data' => $result['data'] ?? $result
                ]);
            }

        } catch (\Exception $e) {
            $this->sendSseEvent('error', [
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // 接続終了イベント
        $this->sendSseEvent('end', ['message' => 'ストリーム終了']);

        return $this->response;
    }

    /**
     * SSEストリーム（複数ブログ記事の一括追加）
     * POST /baser/api/admin/cu-mcp/mcp/stream-bulk-add-blog-posts
     */
    public function streamBulkAddBlogPosts()
    {
        $this->request->allowMethod(['post']);

        // SSEヘッダーを設定
        $this->response = $this->response
            ->withType('text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization');

        if (ob_get_level()) {
            ob_end_clean();
        }

        $posts = $this->request->getData('posts', []);

        if (empty($posts)) {
            $this->sendSseEvent('error', ['message' => 'postsパラメータが必要です']);
            return $this->response;
        }

        $total = count($posts);
        $completed = 0;

        $this->sendSseEvent('start', [
            'message' => "{$total}件のブログ記事の一括追加を開始します",
            'total' => $total
        ]);

        foreach ($posts as $index => $post) {
            try {
                $this->sendSseEvent('progress', [
                    'current' => $index + 1,
                    'total' => $total,
                    'progress' => round((($index + 1) / $total) * 100),
                    'message' => "記事「{$post['title']}」を処理中..."
                ]);

                $result = $this->mcpService->addBlogPost($post);

                if (!empty($result['error'])) {
                    $this->sendSseEvent('item_error', [
                        'index' => $index,
                        'title' => $post['title'],
                        'message' => $result['message'] ?? 'エラー',
                        'error' => $result
                    ]);
                } else {
                    $completed++;
                    $this->sendSseEvent('item_success', [
                        'index' => $index,
                        'title' => $post['title'],
                        'data' => $result['data'] ?? $result
                    ]);
                }

                usleep(200000); // 0.2秒待機

            } catch (\Exception $e) {
                $this->sendSseEvent('item_error', [
                    'index' => $index,
                    'title' => $post['title'] ?? 'Unknown',
                    'message' => $e->getMessage()
                ]);
            }
        }

        $this->sendSseEvent('complete', [
            'message' => "一括追加完了: {$completed}/{$total}件成功",
            'completed' => $completed,
            'total' => $total,
            'success_rate' => round(($completed / $total) * 100, 2)
        ]);

        $this->sendSseEvent('end', ['message' => 'ストリーム終了']);

        return $this->response;
    }

    /**
     * SSEイベントを送信
     *
     * @param string $event イベント名
     * @param array $data データ
     */
    private function sendSseEvent(string $event, array $data): void
    {
        $output = "event: {$event}\n";
        $output .= "data: " . json_encode($data) . "\n\n";

        echo $output;
        flush();
    }
}
