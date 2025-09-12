<?php
namespace CuMcp\Mcp;

use BaserCore\Utility\BcUtil;
use Cake\Routing\Router;

class McpServerManger
{

    /**
     * MCPサーバーを起動
     */
    public function startMcpServer(array $config): array
    {
        try {
            $pidFile = $this->getPidFilePath();
            $logFile = $this->getLogFilePath();
            $cakeCommand = ROOT . DS . 'bin' . DS . 'cake';

            // バックグラウンドでMCPサーバーを起動
            $command = sprintf(
                'cd %s && nohup %s cu_mcp.server --transport=sse --host=%s --port=%s %s > %s 2>&1 & echo $! > %s',
                ROOT,
                $cakeCommand,
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                (BcUtil::isTest())? '--test' : '',
                escapeshellarg($logFile),
                escapeshellarg($pidFile)
            );

            shell_exec($command);

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
    public function stopMcpServer(): array
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
            shell_exec("kill {$pid} 2>&1");

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
    public function getServerStatus(): array
    {
        $pidFile = $this->getPidFilePath();
        $config = $this->getServerConfig();

        $isRunning = $this->isServerRunning();
        $pid = file_exists($pidFile) ? trim(file_get_contents($pidFile)) : null;
        $request = Router::getRequest();
        $protocol = ($request->is('https')) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return [
            'running' => $isRunning,
            'pid' => $pid,
            'proxy_url' => "{$protocol}://{$host}/cu-mcp",
            'internal_url' => "http://{$config['host']}:{$config['port']}",
            'config' => $config
        ];
    }

    /**
     * MCPサーバーが起動しているかチェック
     */
    public function isServerRunning(): bool
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
    public function isProcessRunning(string $pid): bool
    {
        $result = shell_exec("ps -p {$pid} 2>/dev/null");
        return !empty($result) && strpos($result, $pid) !== false;
    }

    /**
     * サーバー設定を取得
     */
    public function getServerConfig(): array
    {
        $configFile = $this->getConfigFilePath();

        $defaultConfig = [
            'host' => '127.0.0.1',
            'port' => '3001'
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
    public function saveServerConfig(array $config): void
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
