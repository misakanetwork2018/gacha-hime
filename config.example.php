<?php

const RESULT_STATUSES = ['待发放', '已发放', '拒绝发放'];

return [
    'maintain' => false,
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
        'bot_token' => ''
    ],
    'mail' => [ // only support sendcloud
        'api_user' => '',
        'api_key' => '',
        'from' => '',
        'fromName' => '',
    ],
    'admin_email' => '',
    'debug' => false,
    'event' => [
        'time' => [],
        'enable' => false,
        'pool' => []
    ]
];
