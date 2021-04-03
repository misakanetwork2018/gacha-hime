<?php

namespace Module;

use Helper\Arr;
use Middleware\Api;

class Sync extends \Module
{
    public function __construct()
    {
        $this->middleware(Api::class);

        parent::__construct();
    }

    public function getResultList()
    {
        $page = $this->request->get('page', 1);
        $size = $this->request->get('size', 10);
        $column = $this->request->get('column');
        $order = $this->request->get('order');
        $query = json_decode($this->request->get('search'), true);

        $whiteList = ['id', 'uid', 'type', 'name', 'status', 'created', 'passed', 'expire'];

        [$offset, $size, $column, $order] = $this->safeQuery($page, $size, $column, $order, $whiteList);

        [$where, $binds] = $this->buildWhereSQL($query, $whiteList);

        $list = $this->db->query($this->buildQuerySQL('select * from result' . $where,
            $column, $order, $size, $offset), $binds, false, function ($item) {
            $item['extra'] = json_decode($item['extra'], true);
            return $item;
        });

        $count = $this->db->query('select count(*) as count from result' . $where,
                $binds, true)['count'] ?? 0;

        return [
            'total' => $count,
            'data' => $list,
        ];
    }

    public function getResult()
    {
        $id = $this->request->get('id');

        $result = $this->db->query('select * from result where id = ?', $id, true);

        $result['extra'] = json_decode($result['extra'], true);

        return $result;
    }

    public function update()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $reason = $this->request->post('reason');
        $extra = $this->request->post('extra');

        if ($status == 1) {
            $sql = '';
            $binds = [time()];

            foreach ($extra as $key => $val) {
                if (!in_array($key, ['code'])) continue;

                $sql .= ", extra = json_set(extra, '$.{$key}', ?)";
                $binds[] = $val;
            }

            $binds[] = $id;

            $success = $this->db->exec("update result set status = 1, passed = ?{$sql} where id = ?", $binds);
        } elseif ($status == 2) {
            $success = $this->db->exec("update result set status = 2, ".
                "extra = json_set(extra, '$.reason', ?) where id = ?", [$reason, $id]);
        } else {
            $success = false;
        }

        return ['success' => $success];
    }

    private function safeQuery($page, $size, $column, $order, $allowColumns = [])
    {
        if (!in_array($column, $allowColumns))
            $column = null;

        if (!in_array($order, ['asc', 'desc']))
            $order = null;

        if (!is_numeric($page) || $page <= 0)
            $page = 1;

        if (!is_numeric($size) || $size < 0)
            $size = 10;

        $offset = ($page - 1) * $size;

        return [$offset, $size, $column, $order];
    }

    private function buildQuerySQL($prefix, $column, $order, $limit = null, $offset = null)
    {
        $sql = $prefix;

        if ($column)
            $sql .= ' order by ' . $column . ($order ? ' ' . $order : '');

        if ($limit)
            $sql .= ' limit ' . ($offset ? $offset . ', ' : '') . $limit;

        return $sql;
    }

    private function buildWhereSQL(array $wheres, $whiteList = [])
    {
        $where_sql = '';
        $binds = [];

        foreach ($wheres as $key => $item) {
            if (!empty($whiteList) && !in_array($key, $whiteList)) continue;

            if (empty($where_sql))
                $sql = ' where ';
            else
                $sql = ' and ';

            $value = $item['value'] ?? null;
            $pass = false;

            switch ($item['type']) {
                case 'equal':
                    if (is_null($value) || $value === '') {
                        $pass = true;
                        break;
                    }
                    $sql .= $key . ' = ?';
                    $binds[] = $value;
                    break;
                case 'like':
                    if (is_null($value) || $value === '') {
                        $pass = true;
                        break;
                    }
                    $sql .= $key . ' like ?';
                    $binds[] = "%$value%";
                    break;
                case 'range':
                    if (isset($item['start'])) {
                        $op = isset($item['start_exclude']) && $item['start_exclude'] ? '>' : '>=';
                        $sql .= $key . ' ' . $op . ' ?';
                        $binds[] = $item['start'];
                    }
                    if (isset($item['end'])) {
                        $op = isset($item['start_exclude']) && $item['start_exclude'] ? '<' : '<=';
                        $sql .= $key . ' ' . $op . ' ?';
                        $binds[] = $item['end'];
                    }
                    if (!isset($item['start']) && !isset($item['end'])) $pass = true;
                    break;
                case 'array':
                    $value = Arr::wrap($value);
                    if (!count($value)) {
                        $pass = true;
                        break;
                    }
                    $sql .= $key . " in (" . implode(",", array_fill(0, count($value), '?')) . ')';
                    $binds = array_merge($binds, $value);
                    break;
            }

            if (!$pass) {
                $where_sql .= $sql;
            }
        }

        return [$where_sql, $binds];
    }

    public function ban()
    {
        $uid = $this->request->post('uid');
        $expire = $this->request->post('expire');
        $reason = $this->request->post('reason');

        $baning = $this->db->query("select count(*) as count from black_list where uid = ? and ".
                "(expire is null or expire > ?)", [$uid, time()], true)['count'] > 0;

        if ($baning)
            return ['success' => false, 'msg' => '已经在封禁状态'];

        $bool = $this->db->exec("insert into black_list (uid, created, reason, expire) values (?, ?, ?, ?)",
            [$uid, time(), $reason, $expire]);

        return ['success' => $bool];
    }

    public function banList()
    {
        $valid = $this->request->get('valid');

        $sql = '';

        if ($valid)
            $sql = ' where expire is null or expire > ?';

        return $this->db->query("select * from black_list" . $sql . ' order by id desc', time());
    }

    public function addGachaTimes()
    {
        $id = $this->request->post('id');
        $add = $this->request->post('add', 1);
        if (empty($id)) return ['success' => false];

        $bool = $this->db->exec("update profile set gacha_times = gacha_times + ? where uid = ?", [$add, $id]);

        return ['success' => $bool];
    }
}