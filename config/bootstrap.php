<?php

use Cake\Log\Log;
use Psr\Log\LogLevel;

Log::write(LogLevel::INFO, $_SERVER['REQUEST_URI'], 'mcp');
if(!empty($_POST)) {
    Log::write(LogLevel::INFO, json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'mcp');
}
