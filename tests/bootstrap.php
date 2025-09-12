<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for CuMcp.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use BaserCore\Utility\BcApiUtil;
use CuMcp\Mcp\McpServerManger;
use josegonzalez\Dotenv\Loader;
use Migrations\TestSuite\Migrator;

$findRoot = function($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once $root . '/vendor/autoload.php';

$env = dirname(__DIR__) . DS . 'tests' . DS . 'TestApp' . DS . 'config' . DS . '.env';
if(file_exists($env)) {
    $dotenv = new Loader([$env]);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

require_once dirname(__DIR__) . '/tests/setup.php';

$mcpServerManager = new McpServerManger();
if(!$mcpServerManager->isServerRunning()) {
    echo "Starting MCP Server...\n";
    $result = $mcpServerManager->startMcpServer($mcpServerManager->getServerConfig());
    echo "MCP Server start result: " . json_encode($result) . "\n";

    // 起動直後のプロセス確認
    sleep(2); // 2秒待機
    $isRunning = $mcpServerManager->isServerRunning();
    echo "MCP Server running after start: " . ($isRunning ? 'YES' : 'NO') . "\n";

    if (!$isRunning) {
        // PIDファイルの確認
        $pidFile = '/Users/ryuring/Projects/baserplugin/plugins/CuMcp/tests/TestApp/tmp/cu_mcp_server.pid';
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            echo "PID file exists with PID: {$pid}\n";

            // プロセスの詳細確認
            $psResult = shell_exec("ps -p {$pid} -o pid,ppid,command 2>/dev/null");
            echo "Process details:\n{$psResult}\n";

            // ログファイルの確認
            $logFile = '/Users/ryuring/Projects/baserplugin/plugins/CuMcp/tests/TestApp/logs/cu_mcp_server.log';
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                echo "Log file content:\n{$logContent}\n";
            } else {
                echo "Log file does not exist\n";
            }
        } else {
            echo "PID file does not exist\n";
        }
    }
} else {
    echo "MCP Server already running\n";
}

if(!file_exists(CONFIG . 'jwt.pem')) {
    BcApiUtil::createJwt();
}

/**
 * Load schema from a SQL dump file.
 *
 * If your plugin does not use database fixtures you can
 * safely delete this.
 *
 * If you want to support multiple databases, consider
 * using migrations to provide schema for your plugin,
 * and using \Migrations\TestSuite\Migrator to load schema.
 */
(new Migrator())->runMany([
    ['plugin' => 'BaserCore'],
    ['plugin' => 'CuMcp'],
    ['plugin' => 'BcBlog'],
    ['plugin' => 'BcCustomContent'],
    ['plugin' => 'BcSearchIndex']
]);
