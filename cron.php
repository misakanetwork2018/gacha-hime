<?php

define('APP_ROOT', __DIR__);

require_once APP_ROOT . "/include/App.php";

App::init();

if (!App::config('cron.enable')) return;

if (preg_match("/cli/i", php_sapi_name())) {
    $args = getopt("k:");
    $key = $args['k'] ?? null;
} else {
    $key = App::make(\Http\Request::class)->get('key');
}

if (App::config('cron.key') != $key) die('err');

$db = App::make(DB::class);

if (App::config('cron.reset')) {
    $db->exec("update profile set gacha_times = ?", App::config('cron.gacha_times'));
}

$db->exec("update result set status = 2, extra = json_set(extra, '$.reason', '已超过兑换时间') where ".
    "extra->'$.exchange' = false and expire < ?", time());

echo 'ok';