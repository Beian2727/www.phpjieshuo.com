<?php
/**
 * 文章表。
 * @author fingerQin
 * @date 2016-03-27
 */

namespace models;

use common\YCore;

class News extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_news';

    /**
     * 文章列表。
     *
     * @param  string   $title      文章标题。
     * @param  int      $adminId    管理员ID。
     * @param  string   $starttime  开始时间。
     * @param  string   $endtime    截止时间。
     * @param  int      $page       分页页码。
     * @param  int      $count      每页显示记录条数。
     * @return array
     */
    public function getList($title = '', $adminId = -1, $starttime = '', $endtime = '', $page, $count)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = ' * ';
        $where   = ' WHERE status = :status ';
        $params  = [
            ':status' => self::STATUS_NORMAL
        ];
        if (strlen($title) > 0) {
            $where .= ' AND title LIKE :title ';
            $params[':title'] = "%{$title}%";
        }
        if (strlen($starttime) > 0) {
            $where .= ' AND created_time > :starttime ';
            $params[':starttime'] = strtotime($starttime);
        }
        if (strlen($endtime) > 0) {
            $where .= ' AND created_time < :endtime ';
            $params[':endtime'] = strtotime($endtime);
        }
        if ($adminId != self::NONE) {
            $where .= ' AND created_by = :admin_id ';
            $params[':admin_id'] = $adminId;
        }
        $orderBy   = ' ORDER BY news_id DESC ';
        $sql       = "SELECT COUNT(1) AS count FROM {$this->tableName} {$where}";
        $countData = $this->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM {$this->tableName} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $this->rawQuery($sql, $params)->rawFetchAll();
        foreach ($list as $k => $item) {
            $item['modified_time'] = YCore::formatDateTime($item['modified_time']);
            $list[$k]              = $item;
        }
        $result = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => $this->IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }
}
