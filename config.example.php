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
    'telegram' => [
        'notify' => true,
        'chat_id' => '',
        'bot_key' => ''
    ],
    'mail' => [ // only support mailgun
        'api_key' => '',
        'domain' => '',
        'from' => '',
    ],
    'admin_email' => '',
    'debug' => false,
    'event' => [
        'time' => [],
        'enable' => false,
        'pool' => []
    ]
];
