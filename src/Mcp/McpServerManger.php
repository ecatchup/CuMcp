<?php
namespace CuMcp\Mcp;
use Cake\Routing\Router;
use Cake\Http\Client;

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

            // 複数のパスでcakeコマンドを探す
            $possibleCakePaths = [
                ROOT . DS . 'bin' . DS . 'cake',                    // 通常のパス
                ROOT . DS . 'vendor' . DS . 'bin' . DS . 'cake',    // Composerでインストールされた場合
                dirname(ROOT) . DS . 'bin' . DS . 'cake',           // 親ディレクトリにある場合
                '/usr/local/bin/cake',                              // グローバルインストール
                'cake'                                              // PATHにある場合
            ];

            $cakeCommand = null;
            foreach ($possibleCakePaths as $path) {
                if (file_exists($path) && is_executable($path)) {
                    $cakeCommand = $path;
                    break;
                }
            }

            // PHPから直接実行する方法も試す
            if (!$cakeCommand) {
                // vendor/bin/cake のPHPスクリプトを直接実行
                $phpCakePath = ROOT . DS . 'vendor' . DS . 'bin' . DS . 'cake';
                if (file_exists($phpCakePath)) {
                    $cakeCommand = 'php ' . $phpCakePath;
                }
            }

            // デバッグ情報をログ出力
            error_log("=== MCP Server Start Debug ===");
            error_log("ROOT: " . ROOT);
            error_log("Possible cake paths checked:");
            foreach ($possibleCakePaths as $path) {
                error_log("  {$path}: " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') .
                         (file_exists($path) && is_executable($path) ? ' (EXECUTABLE)' : ''));
            }
            error_log("Selected Cake Command: " . ($cakeCommand ?? 'NONE'));
            error_log("PID File: " . $pidFile);
            error_log("Log File: " . $logFile);
            error_log("Config: " . json_encode($config));
            error_log("Current working directory: " . getcwd());
            error_log("Environment PATH: " . ($_ENV['PATH'] ?? 'Not set'));
            error_log("GitHub Actions environment: " . (getenv('GITHUB_ACTIONS') ? 'YES' : 'NO'));

            if (!$cakeCommand) {
                error_log("ERROR: No cake command found");
                return ['success' => false, 'message' => 'Cakeコマンドが見つかりません'];
            }

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

            error_log("Command to execute: " . $command);

            $shellOutput = shell_exec($command);
            error_log("Shell exec output: " . ($shellOutput ?? 'NULL'));

            // PIDファイルの確認
            if (file_exists($pidFile)) {
                $pid = trim(file_get_contents($pidFile));
                error_log("PID file created with PID: " . $pid);
            } else {
                error_log("PID file was not created");
            }

            // ログファイルの初期内容確認
            if (file_exists($logFile)) {
                $initialLogContent = file_get_contents($logFile);
                error_log("Initial log file content: " . $initialLogContent);
            } else {
                error_log("Log file was not created");
            }

            // 起動確認（最大10秒待機）
            $attempts = 0;
            while ($attempts < 20 && !$this->isServerRunning()) {
                error_log("Attempt " . ($attempts + 1) . ": Server not running yet");
                usleep(500000); // 0.5秒待機
                $attempts++;
            }

            if ($this->isServerRunning()) {
                error_log("MCP Server started successfully");
                return ['success' => true, 'message' => 'MCPサーバーが正常に起動しました'];
            } else {
                $logContent = file_exists($logFile) ? file_get_contents($logFile) : 'ログファイルが見つかりません';
                error_log("MCP Server failed to start. Log content: " . $logContent);
                return ['success' => false, 'message' => 'サーバーの起動を確認できませんでした。ログ: ' . $logContent];
            }

        } catch (\Exception $e) {
            error_log("Exception in startMcpServer: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
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

        error_log("Checking server running status:");
        error_log("PID file path: " . $pidFile);
        error_log("PID file exists: " . (file_exists($pidFile) ? 'YES' : 'NO'));

        if (!file_exists($pidFile)) {
            error_log("PID file does not exist");
            return false;
        }

        $pid = trim(file_get_contents($pidFile));
        error_log("PID from file: " . $pid);

        $isRunning = $pid && $this->isProcessRunning($pid);
        error_log("Final server running status: " . ($isRunning ? 'YES' : 'NO'));

        return $isRunning;
    }

    /**
     * プロセスが実行中かチェック
     */
    public function isProcessRunning(string $pid): bool
    {
        $result = shell_exec("ps -p {$pid} 2>/dev/null");
        $isRunning = !empty($result) && strpos($result, $pid) !== false;

        error_log("Process check for PID {$pid}:");
        error_log("PS command result: " . ($result ?? 'NULL'));
        error_log("Is running: " . ($isRunning ? 'YES' : 'NO'));

        return $isRunning;
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
     * MCPサーバーが起動しているかチェック
     */
    public function isMcpServerRunning(array $config): bool
    {
        try {
            $client = new Client(['timeout' => 3]);
            // POSTリクエストでサーバーの生存確認（軽量なリクエスト）
            $response = $client->post("http://127.0.0.1:{$config['port']}/", json_encode([
                'jsonrpc' => '2.0',
                'id' => 'ping',
                'method' => 'tools/list'  // 実際に存在するメソッドを使用
            ]), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            // レスポンスが返ってきたらサーバーが起動していると判定
            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            return false;
        }
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
