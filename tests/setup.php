<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;
use CuMcp\CuMcpPlugin;

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
