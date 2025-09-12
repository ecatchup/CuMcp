<?php
// created by BcInstaller
return [
    'Datasources.default' => [
        'className' => 'Cake\\Database\\Connection',
        'driver' => 'Cake\\Database\\Driver\\Mysql',
        'host' => env('DB_HOST_NAME', '127.0.0.1'),
        'port' => '3306',
        'username' => 'root',
        'password' => 'root',
        'database' => 'test_basercms',
        'prefix' => '',
        'schema' => '',
        'persistent' => '',
        'encoding' => 'utf8mb4',
        'log' => filter_var(env('SQL_LOG', false), FILTER_VALIDATE_BOOLEAN)
    ],
    'Datasources.test' => [
        'className' => 'Cake\\Database\\Connection',
        'driver' => 'Cake\\Database\\Driver\\Mysql',
        'host' => env('DB_HOST_NAME', '127.0.0.1'),
        'port' => '3306',
        'username' => 'root',
        'password' => 'root',
        'database' => 'test_basercms',
        'prefix' => '',
        'schema' => '',
        'persistent' => '',
        'encoding' => 'utf8mb4',
        'log' => filter_var(env('SQL_LOG', false), FILTER_VALIDATE_BOOLEAN)
    ]
];
