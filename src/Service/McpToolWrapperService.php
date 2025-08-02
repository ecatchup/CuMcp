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

namespace CuMcp\Service;

use CuMcp\McpServer\BaserCmsMcpServer;
use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * MCPツールをHTTP APIで利用するためのラッパーサービス
 */
class McpToolWrapperService
{
    private BaserCmsMcpServer $mcpServer;

    public function __construct()
    {
        $this->mcpServer = new BaserCmsMcpServer();
    }

    /**
     * ブログ記事追加ツールを実行
     *
     * @param array $params リクエストパラメータ
     * @return array 実行結果
     * @throws \Exception
     */
    public function addBlogPost(array $params): array
    {
        try {
            // バリデーション
            $this->validateAddBlogPostParams($params);

            // MCPツールのハンドラーを直接呼び出し
            $result = $this->mcpServer->addBlogPost($params);

            Log::info('ブログ記事追加成功', [
                'title' => $params['title'],
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('ブログ記事追加失敗', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ブログ記事追加パラメータのバリデーション
     *
     * @param array $params
     * @throws \InvalidArgumentException
     */
    private function validateAddBlogPostParams(array $params): void
    {
        if (empty($params['title'])) {
            throw new \InvalidArgumentException('タイトルは必須です');
        }

        if (empty($params['detail'])) {
            throw new \InvalidArgumentException('詳細は必須です');
        }

        // 文字数制限
        if (mb_strlen($params['title']) > 255) {
            throw new \InvalidArgumentException('タイトルは255文字以内で入力してください');
        }

        // カテゴリの存在確認（指定がある場合）
        if (!empty($params['category'])) {
            // カテゴリの妥当性チェックはMCPサーバー側で実行される
        }
    }

    /**
     * MCPツールの情報を取得
     *
     * @return array
     */
    public function getToolInfo(): array
    {
        return [
            'name' => 'addBlogPost',
            'description' => 'ブログ記事を追加します',
            'parameters' => [
                'title' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => '記事タイトル'
                ],
                'detail' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => '記事詳細'
                ],
                'category' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'カテゴリ名'
                ],
                'blog_content' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'ブログコンテンツ名'
                ],
                'email' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'ユーザーのメールアドレス'
                ]
            ]
        ];
    }
}
