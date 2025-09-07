<?php

use Cake\Core\Configure;
use Cake\Log\Log;
use Psr\Log\LogLevel;

Log::setConfig(Configure::consume('Log'));

Log::write(LogLevel::INFO, '-----------------------------------------------', 'mcp');
Log::write(LogLevel::INFO, $_SERVER['REQUEST_URI'], 'mcp');
Log::write(LogLevel::INFO, $_SERVER['REQUEST_METHOD'], 'mcp');
Log::write(LogLevel::INFO, json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'mcp');
if(!empty($_POST)) {
    Log::write(LogLevel::INFO, json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'mcp');
}
Log::write(LogLevel::INFO, '-----------------------------------------------', 'mcp');
