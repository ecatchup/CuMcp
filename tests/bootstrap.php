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
    $mcpServerManager->startMcpServer($mcpServerManager->getServerConfig());
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
