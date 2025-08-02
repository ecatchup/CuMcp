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

namespace CuMcp\Controller\Admin;

use BaserCore\Controller\Admin\BcAdminAppController;
use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\Http\Client;

/**
 * MCPサーバー管理コントローラー
 * 管理画面からMCPサーバーの起動・停止・設定を行う
 */
class McpServerManagerController extends BcAdminAppController
{
    /**
     * 初期化
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->set('title', 'MCPサーバー管理');
    }

    /**
     * MCPサーバー管理画面
     */
    public function index()
    {
        $status = $this->getServerStatus();
        $config = $this->getServerConfig();

        $this->set(compact('status', 'config'));
    }

    /**
     * MCPサーバー起動
     */
    public function start()
    {
        $this->request->allowMethod(['post']);

        try {
            if ($this->isServerRunning()) {
                $this->BcMessage->setError('MCPサーバーは既に起動しています');
                return $this->redirect(['action' => 'index']);
            }

            $config = $this->getServerConfig();
            $result = $this->startMcpServer($config);

            if ($result['success']) {
                $this->BcMessage->setSuccess('MCPサーバーを起動しました');
            } else {
                $this->BcMessage->setError('MCPサーバーの起動に失敗しました: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->BcMessage->setError('MCPサーバーの起動中にエラーが発生しました: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * MCPサーバー停止
     */
    public function stop()
    {
        $this->request->allowMethod(['post']);

        try {
            $result = $this->stopMcpServer();

            if ($result['success']) {
                $this->BcMessage->setSuccess('MCPサーバーを停止しました');
            } else {
                $this->BcMessage->setError('MCPサーバーの停止に失敗しました: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->BcMessage->setError('MCPサーバーの停止中にエラーが発生しました: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * MCPサーバー再起動
     */
    public function restart()
    {
        $this->request->allowMethod(['post']);

        try {
            // 停止
            if ($this->isServerRunning()) {
                $this->stopMcpServer();
                sleep(2); // 少し待機
            }

            // 起動
            $config = $this->getServerConfig();
            $result = $this->startMcpServer($config);

            if ($result['success']) {
                $this->BcMessage->setSuccess('MCPサーバーを再起動しました');
            } else {
                $this->BcMessage->setError('MCPサーバーの再起動に失敗しました: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->BcMessage->setError('MCPサーバーの再起動中にエラーが発生しました: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * 設定画面
     */
    public function configure()
    {
        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();

            try {
                $this->saveServerConfig($data);
                $this->BcMessage->setSuccess('設定を保存しました');
                return $this->redirect(['action' => 'index']);

            } catch (\Exception $e) {
                $this->BcMessage->setError('設定の保存に失敗しました: ' . $e->getMessage());
            }
        }

        $config = $this->getServerConfig();
        $this->set(compact('config'));
    }

    /**
     * MCPサーバーを起動
     */
    private function startMcpServer(array $config): array
    {
        try {
            $pidFile = $this->getPidFilePath();
            $logFile = $this->getLogFilePath();
            $cakeCommand = ROOT . DS . 'bin' . DS . 'cake';

            // バックグラウンドでMCPサーバーを起動
            $command = sprintf(
                'cd %s && nohup %s cu_mcp.server --transport=sse --host=%s --port=%s > %s 2>&1 & echo $! > %s',
                ROOT,
                $cakeCommand,
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($logFile),
                escapeshellarg($pidFile)
            );

            $output = shell_exec($command);

            // 起動確認（最大10秒待機）
            $attempts = 0;
            while ($attempts < 20 && !$this->isServerRunning()) {
                usleep(500000); // 0.5秒待機
                $attempts++;
            }

            if ($this->isServerRunning()) {
                return ['success' => true, 'message' => 'MCPサーバーが正常に起動しました'];
            } else {
                $logContent = file_exists($logFile) ? file_get_contents($logFile) : 'ログファイルが見つかりません';
                return ['success' => false, 'message' => 'サーバーの起動を確認できませんでした。ログ: ' . $logContent];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * MCPサーバーを停止
     */
    private function stopMcpServer(): array
    {
        try {
            $pidFile = $this->getPidFilePath();

            if (!file_exists($pidFile)) {
                return ['success' => false, 'message' => 'PIDファイルが見つかりません（サーバーが起動していない可能性があります）'];
            }

            $pid = trim(file_get_contents($pidFile));

            if (!$pid || !$this->isProcessRunning($pid)) {
                unlink($pidFile);
                return ['success' => false, 'message' => 'MCPサーバーのプロセスが見つかりません'];
            }

            // プロセスを停止
            $killResult = shell_exec("kill {$pid} 2>&1");

            // 停止確認
            sleep(1);
            if (!$this->isProcessRunning($pid)) {
                unlink($pidFile);
                return ['success' => true, 'message' => 'MCPサーバーを正常に停止しました'];
            } else {
                // 強制終了
                shell_exec("kill -9 {$pid} 2>&1");
                unlink($pidFile);
                return ['success' => true, 'message' => 'MCPサーバーを強制終了しました'];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * サーバーの状態を取得
     */
    private function getServerStatus(): array
    {
        $pidFile = $this->getPidFilePath();
        $config = $this->getServerConfig();

        $isRunning = $this->isServerRunning();
        $pid = file_exists($pidFile) ? trim(file_get_contents($pidFile)) : null;

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return [
            'running' => $isRunning,
            'pid' => $pid,
            'proxy_url' => "{$protocol}://{$host}/cu-mcp/mcp-proxy",
            'internal_url' => "http://{$config['host']}:{$config['port']}",
            'chatgpt_url' => "{$protocol}://{$host}/cu-mcp/mcp-proxy",
            'config' => $config
        ];
    }

    /**
     * MCPサーバーが起動しているかチェック
     */
    private function isServerRunning(): bool
    {
        $pidFile = $this->getPidFilePath();

        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = trim(file_get_contents($pidFile));
        return $pid && $this->isProcessRunning($pid);
    }

    /**
     * プロセスが実行中かチェック
     */
    private function isProcessRunning(string $pid): bool
    {
        $result = shell_exec("ps -p {$pid} 2>/dev/null");
        return !empty($result) && strpos($result, $pid) !== false;
    }

    /**
     * サーバー設定を取得
     */
    private function getServerConfig(): array
    {
        $configFile = $this->getConfigFilePath();

        $defaultConfig = [
            'host' => '127.0.0.1',
            'port' => '3000',
            'auto_start' => false,
            'log_level' => 'info'
        ];

        if (file_exists($configFile)) {
            $savedConfig = json_decode(file_get_contents($configFile), true);
            return array_merge($defaultConfig, $savedConfig ?: []);
        }

        return $defaultConfig;
    }

    /**
     * サーバー設定を保存
     */
    private function saveServerConfig(array $config): void
    {
        $configFile = $this->getConfigFilePath();
        $configDir = dirname($configFile);

        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($configFile, $jsonConfig);
    }

    /**
     * PIDファイルのパスを取得
     */
    private function getPidFilePath(): string
    {
        return TMP . 'cu_mcp_server.pid';
    }

    /**
     * ログファイルのパスを取得
     */
    private function getLogFilePath(): string
    {
        return LOGS . 'cu_mcp_server.log';
    }

    /**
     * 設定ファイルのパスを取得
     */
    private function getConfigFilePath(): string
    {
        return CONFIG . 'cu_mcp_server.json';
    }
}
