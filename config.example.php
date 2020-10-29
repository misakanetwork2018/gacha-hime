<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'gacha',
        'charset' => 'utf8'
    ],
    'api' => '',
    'key' => '',
    'cron' => [
        'key' => '',
        'enable' => true,
        'reset' => true,
        'gacha_times' => 1
    ],
    'debug' => false,
    'event' => [
        'time' => [],
        'enable' => false,
        'pool' => []
    ]
];
