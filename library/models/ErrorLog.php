<?php
/**
 * 日志模型。
 * @author fingerQin
 * @date 2015-11-03
 */

namespace models;

use common\YCore;

class ErrorLog extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName   = 'ms_error_log';

    /**
     * 添加日志。
     *
     * @param  string  $content  日志内容。
     * @param  string  $logTime  日志产生时间。
     * @param  int     $errcode  错误编号。
     * @return bool
     */
    public function addLog($content, $logTime, $errcode = 0)
    {
        $data = [
            'log_time'     => $logTime,
            'content'      => $content,
            'created_time' => date('Y-m-d H:i:s', time()),
            'errcode'      => $errcode
        ];
        $insert_id = $this->insert($data);
        return $insert_id > 0 ? true : false;
    }

    /**
     * 获取日志列表。
     *
     * @param  int   $errcode    错误编码。
     * @param  int   $starttime  日志产生查询开始时间。
     * @param  int   $endtime    日志产生查询结束时间。
     * @param  int   $page       页码。
     * @param  int   $count      每页显示条数。
     * @return array
     */
    public function getList($errcode = 0, $starttime = '', $endtime = '', $page = 1, $count = 10)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = ' * ';
        $where   = ' WHERE 1 = 1 ';
        $params  = [];
        if ($errcode > 0) {
            $where .= ' AND errcode = :errcode ';
            $params[':errcode'] = $errcode;
        }
        if ($starttime > 0 && $endtime > 0) {
            $where .= ' AND log_time BETWEEN :start_log_time AND :end_log_time ';
            $params[':start_log_time'] = $starttime;
            $params[':end_log_time']   = $endtime;
        }
        $orderBy   = ' ORDER BY log_id DESC ';
        $sql       = "SELECT COUNT(1) AS count FROM {$this->tableName} {$where}";
        $countData = $this->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM {$this->tableName} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $this->rawQuery($sql, $params)->rawFetchAll();
        $result    = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => $this->IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

}
