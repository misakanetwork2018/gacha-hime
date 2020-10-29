<?php

namespace Module;

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
        $query = $this->request->get();
        unset($query['page'], $query['size'], $query['column'], $query['order']);

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

        return $this->db->query('select * from result where id = ?', $id, true);
    }

    public function update()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $reason = $this->request->post('reason');

        if ($status == 1) {
            $success = $this->db->exec('update result set status = 1 where id = ?', $id);
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
        $sql = '';
        $binds = [];

        foreach ($wheres as $key => $val) {
            if (!empty($whiteList) && !in_array($key, $whiteList)) continue;

            if (empty($sql))
                $sql = ' where ';
            else
                $sql .= ' and ';

            $sql .= "$key = ?";
            $binds[] = $val;
        }

        return [$sql, $binds];
    }
}