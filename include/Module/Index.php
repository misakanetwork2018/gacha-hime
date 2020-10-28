<?php

namespace Module;

use App;
use DB;
use Http\Redirect;
use View;

class Index extends \Module
{
    public function __construct()
    {
        $this->middleware(\Middleware\Auth::class);

        parent::__construct();
    }

    public function index()
    {
        $user_id = $_SESSION['user']['id'];

        $profile = $this->db->query("select * from profile where uid = ?", $user_id, true);

        $result = $this->db->query("select * from result where uid = ? order by created desc", $user_id,
            false, function ($item) {
            $item['extra'] = json_decode($item['extra'], true);
            $item['status'] = RESULT_STATUSES[$item['status']];
            $item['created'] = date("Y-m-d H:i:s", $item['created']);
            $item['passed'] = $item['passed'] ? date("Y-m-d H:i:s", $item['passed']) : '-';
            $item['description'] = $this->generateDescription($item);
            return $item;
        });

        return View::make('index', ['times' => $profile['gacha_times'], 'result' => $result]);
    }

    public function gacha()
    {
        $user_id = $_SESSION['user']['id'];

        $profile = $this->db->query("select * from profile where uid = ?", $user_id, true);

        if ($profile['gacha_times'] <= 0) {
            return ['success' => false, 'msg' => '抽奖剩余次数用尽'];
        }

        $pool = App::config('event.pool');

        $res = $this->gachaRun($pool);

        $info = $pool[$res];

        ['type' => $type, 'name' => $name, 'data' => $data] = $info;

        $info['expire'] = null;
        $msg = '奖品将在24小时内发放';
        if ($type === 'trf_pkg') {
            $info['expire'] = strtotime("+7days"); // 七天内领取
            $data['exchange'] = false;
            $msg .= '，流量包产品需要手动兑换，请在' . date("Y-m-d H:i:s", $info['expire']) . "之前兑换，逾期无效";
        }

        $info['extra'] = $data;

        $this->db->beginTransaction();

        try {
            $this->db->exec("insert into result (uid, type, name, extra, created, expire) VALUES (?, ?, ?, ?, ?, ?)",
                [$user_id, $type, $name, json_encode($data), $created = time(), $info['expire']]);

            $info['id'] = $this->db->lastInsertId();

            $this->db->exec("update profile set gacha_times = if(gacha_times < 1, 0, gacha_times - 1) where uid = ?",
                $user_id);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'msg' => '出了一点问题，稍后再试吧'];
        }

        return ['success' => true, 'msg' => $msg, 'result' => [
            'id' => $info['id'],
            'name' => $name,
            'status' => RESULT_STATUSES[0],
            'description' => $this->generateDescription($info),
            'created' => date("Y-m-d H:i:s", $created)
        ], 'gacha' => [
            'total' => count($pool),
            'times' => count($pool) * random_int(5, 8) + $res,
            'rest' => $profile['gacha_times'] - 1
        ]];
    }

    private function generateDescription($info)
    {
        switch ($info['type']) {
            case 'trf_pkg':
                $expire = date("Y-m-d H:i:s", $info['expire']);
                return <<<HTML
请在{$expire}之前兑换
<button class="button button-primary" onclick="exchange('{$info['id']}')">兑换</button>
HTML;
            case 'promotion':
                return $info['extra']['promotion'] ?? '';
            default:
                return '';
        }
    }

    private function gachaRun($pool)
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

    public function exchangeCheck()
    {
        $id = $this->request->post('id');

        $result = $this->db->query("select * from result where id = ?", $id, true);

        if (is_null($result))
            return ['allowed' => false, 'reason' => '奖品不存在'];

        $result['extra'] = json_decode($result['extra'], true);

        if (!isset($result['extra']['exchange']))
            return ['allowed' => false, 'reason' => '奖品无需兑换'];

        if ($result['extra']['exchange'])
            return ['allowed' => false, 'reason' => '奖品已被兑换'];

        switch ($result['type']) {
            case 'trf_pkg':
                $data = App::make(\Api::class)->getServiceBaseType($_SESSION['token'], $result['extra']['product']);

                $options = [];

                foreach ($data['field'][0]['list'] as $option) {
                    $options[$option['value']] = $option['label'];
                }

                $field = [
                    ['type' => 'select', 'name' => 'aaa', 'label' => '绑定底包', 'required' => true, 'options' => $options]
                ];
                break;
            default:
                return ['allowed' => false, 'reason' => '奖品无需兑换'];
        }

        return ['allowed' => true, 'field' => $field];
    }

    public function exchange()
    {
        $id = $this->request->post('id');

        $result = $this->db->query("select * from result where id = ?", $id, true);

        if (is_null($result))
            return '奖品不存在';

        $result['extra'] = json_decode($result['extra'], true);

        if (!isset($result['extra']['exchange']) || !in_array($result['type'], ['trf_pkg']))
            return '奖品无需兑换';

        if ($result['extra']['exchange'])
            return '奖品已被兑换';



        return Redirect::to('/');
    }
}