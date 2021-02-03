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
    $result = curl_post("https://api.sendcloud.net/apiv2/mail/send", [
        'apiUser' => App::config('api_user'), # 使用api_user和api_key进行验证
        'apiKey' => App::config('api_key'),
        'from' => App::config('mail.from'), # 发信人，用正确邮件地址替代
        'fromName' => App::config('mail.fromName'),
        'to' => $mail['to'],# 收件人地址, 用正确邮件地址替代, 多个地址用';'分隔
        'subject' => $mail['subject'],
        'plain' => $mail['content']
    ]);
    echo "send mail: \n" . json_encode($result) . PHP_EOL;

    $db->exec("update mail_list set sent = ? where id = ?", [time(), $mail['id']]);

    if ($mail['to'] == App::config('admin_email') && App::config('telegram.notify'))
        curl_post("https://api.telegram.org/bot" . App::config('telegram.bot_token') .
            "/sendMessage", ["chat_id" => App::config('telegram.chat_id'),
            "text" => "中奖列表已更新，请前去审核"]);
}

echo 'ok';