#!/usr/bin/php -q
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Console\CommandRunner;

if(in_array('--test', array_values($argv))) {
    require_once dirname(__DIR__) . '/tests/setup.php';
    unset($argv[array_search('--test', $argv)]);
}

// Build the runner with an application and root executable name.
//$runner = new CommandRunner(new Application(dirname(__DIR__) . '/config'), 'cake');
$runner = new CommandRunner(new Application(CONFIG), 'cake');
exit($runner->run($argv));
