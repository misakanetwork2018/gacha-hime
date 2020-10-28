<?php
// 抽奖概率测试
// 时间超级长，建议在cli上运行
// 这也证明了样本量越大越接近

$config = include "../config.php";

$pool = $config['event']['pool'];

$test = [100, 200, 500, 1000, 2000, 5000, 10000];

foreach ($test as $times) {
    echo $times . " times result:\n";
    validate($pool, $times);
}

function validate($pool, $times = 2000)
{
    $validation = [];

    $start = microtime(true);

    for ($i = 0; $i < $times; $i++) {
        $res = gachaRun($pool);

        if (isset($validation[$res]))
            $validation[$res]++;
        else
            $validation[$res] = 1;
    }

    foreach ($validation as $index => $val) {
        $percent = round($val / $times * 100, 2);

        $name = $pool[$index]['name'];

        $diff = $percent - $pool[$index]['weight'];

        echo "$name: $percent%, 误差: $diff%\n";
    }

    $end = microtime(true);

    echo "Use Time: " . ($end - $start) . "s\n";
}

function gachaRun($pool)
{
    $base = 1000; //权重倍数

    $samples = [];

    $count = 0;

    foreach ($pool as $index => $item) {
        // 填充当前index到样本中
        $samples = array_merge($samples, array_fill(0, $item['weight'] * $base, $index));

        $count += $item['weight'] * $base;
    }

    shuffle($samples);

    $select = mt_rand(0, $count - 1);

    return $samples[$select];
}