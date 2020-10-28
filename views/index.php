<!DOCTYPE>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>抽奖姬 - 御坂网络</title>
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="/static/css/mobile.css">
</head>
<body>
<div class="container">
    <div class="left">
        <div class="auth">
            <div>
                <img class="avatar" alt="头像" src="<?php echo $_SESSION['user']['avatar'] ?>">
                <span class="username">当前登录用户：<?php echo $_SESSION['user']['username'] ?></span>
            </div>
            <div>
                <span class="logout"><a href="/auth/logout">登出</a></span>
            </div>
        </div>
        <h4 class="text-center">欢迎使用抽奖姬</h4>
        <?php if ($this->config->get('event.enable')) : ?>
            <p class="text-center">本次奖池</p>
        <div class="gacha-pool">
            <?php foreach ($this->config->get('event.pool') as $item): ?>
                <div class="item"><?php echo $item['name'] ?></div>
            <?php endforeach; ?>
        </div>
        <p class="text-center">剩余抽奖次数：<span id="gacha-times"><?php $this->times() ?></span></p>
        <p class="text-center"><button class="button gacha" onclick="gacha()">点击抽奖</button></p>
        <p class="text-center">
            <?php [$start, $end] = $this->config->get('event.time') ?>
            活动时间：<?php echo date("Y-m-d H:i:s", $start) ?> ~ <?php echo date("Y-m-d H:i:s", $end) ?>
        </p>
        <?php else: ?>
        <p class="text-center">当前不是活动时间，无法抽卡哦</p>
        <?php endif ?>
        <hr>
        <p class="text-center">我的奖品</p>
        <div class="responsive">
            <table class="list">
                <thead>
                <tr>
                    <th>#</th>
                    <th style="width: 80px;">奖项名称</th>
                    <th style="width: 100px;">状态</th>
                    <th>备注</th>
                    <th style="min-width: 100px;">中奖时间</th>
                    <th style="min-width: 100px;">发放时间</th>
                </tr>
                </thead>
                <tbody id="result-table">
                <?php foreach ($this->result as $item): ?>
                    <tr>
                        <td><?php echo $item['id'] ?></td>
                        <td><?php echo $item['name'] ?></td>
                        <td><?php echo $item['status'] ?></td>
                        <td><?php echo $item['description'] ?></td>
                        <td><?php echo $item['created'] ?></td>
                        <td><?php echo $item['passed'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="right"></div>
</div>
<div class="footer">
    Powered by Misaka Network
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/static/js/app.js"></script>
</body>
</html>