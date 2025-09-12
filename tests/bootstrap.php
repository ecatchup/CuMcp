<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for CuMcp.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use Migrations\TestSuite\Migrator;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;
use CuMcp\CuMcpPlugin;

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

define('ROOT', dirname(__DIR__));
define('APP', ROOT . DS . 'plugins' . DS . 'CuMcp' . DS . 'tests' . DS . 'TestApp' . DS);
define('TESTS', ROOT . DS . 'tests' . DS);
define('APP_DIR', 'src');
define('APP_PATH', ROOT . DS . 'tests' . DS . 'TestApp' . DS);
define('CONFIG', APP_PATH . 'config' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('LOGS', APP_PATH . 'logs' . DS);
define('WWW_ROOT', APP_PATH . 'webroot' . DS);
define('RESOURCES', ROOT . DS . 'resources' . DS);
define('TMP', APP_PATH . 'tmp' . DS);
define('CACHE', TMP . 'cache' . DS);

require CORE_PATH . 'config' . DS . 'bootstrap.php';
require CAKE . 'functions.php';

if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
    $dotenv = new \josegonzalez\Dotenv\Loader([CONFIG . '.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);
Configure::load('app_local', 'default');
Cache::setConfig(Configure::consume('Cache'));
Security::setSalt(Configure::consume('Security.salt'));

ConnectionManager::drop('default');
ConnectionManager::drop('test');
Configure::load('install');
ConnectionManager::setConfig(Configure::consume('Datasources'));
Plugin::getCollection()->add(new CuMcpPlugin());

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
