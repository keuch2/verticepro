<?php
// Copy to config.php and adjust for your environment.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'verticepro',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'base_url' => 'http://localhost:8090',
    'base_path' => '', // Empty if site is at domain root; '/verticepro' if under subpath
    'img_path' => __DIR__ . '/../img',
    'img_url'  => '/img',
    'session_name' => 'verticepro_admin',
    'env' => 'development', // development | production
];
