<?php

define('APP_ROOT', __DIR__);

define('CLI_MODE', preg_match("/cli/i", php_sapi_name()));

require_once APP_ROOT . "/include/App.php";

App::init();

if (!App::config('cron.enable')) return;

if (CLI_MODE) {
    $args = getopt("k:");
    $key = $args['k'] ?? null;
} else {
    $key = App::make(\Http\Request::class)->get('key');
}

if (App::config('cron.key') != $key) die('err');

$db = App::make(DB::class);

if (App::config('cron.reset')) {
    if (file_exists($nextResetTimeFile = APP_ROOT . '/runtime/nextResetTime'))
        $nextResetTime = file_get_contents($nextResetTimeFile);

    if (!isset($nextResetTime) || time() > $nextResetTime) {
        $db->exec("update profile set gacha_times = ?", App::config('cron.gacha_times'));
        file_put_contents($nextResetTimeFile, strtotime('next day 0:00'));
    }
}

$db->exec("update result set status = 2, extra = json_set(extra, '$.reason', '已超过兑换时间') where " .
    "extra->'$.exchange' = false and expire < ? and status = 0", time());

$mail_list = $db->query("select * from mail_list where sent is null");

foreach ($mail_list as $mail) {
    $data = http_build_query(array(
        'from' => App::config('mail.from'),
        'to' => $mail['to'],
        'subject' => $mail['subject'],
        'text' => $mail['content']
    ));
    $url = "https://api.mailgun.net/v3/" . App::config('mail.domain') . "/messages";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, "api:" . App::config('mail.api_key'));
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    curl_close($ch);

    $db->exec("update mail_list set sent = ? where id = ?", [time(), $mail['id']]);
}

echo 'ok';