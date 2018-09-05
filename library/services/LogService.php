<?php
/**
 * 日志管理。
 * @author fingerQin
 * @date 2016-3-15
 */

namespace services;

use common\YCore;
use models\ErrorLog;
use models\Admin;
use models\User;

class LogService extends AbstractService
{
    /**
     * 获取日志列表数据。
     * -- Example start --
     * $options = [
     *      'starttime' => '开始时间。必传。',
     *      'endtime'   => '结束时间。必传。',
     *      'errcode'   => '错误码。必传。',
     *      'content'   => '日志内容。必传。',
     *      'page'      => '当前页码。必传。',
     *      'count'     => '每页显示条数。必传。',
     * ];
     * -- Example end --
     *
     * @param  array $options 参数。
     * @return array
     */
    public static function getLogList($options)
    {
        $errcode   = $options['errcode'];
        $page      = $options['page'];
        $count     = $options['count'];
        $starttime = 0;
        $endtime   = 0;
        if (strlen($options['starttime']) > 0 && strlen($options['endtime']) > 0) {
            if ($options['starttime'] > $options['endtime']) {
                YCore::exception(STATUS_ERROR, '开始时间必须小于等于结束时间');
            }
        }
        $ErrorLogModel = new ErrorLog();
        return $ErrorLogModel->getList($errcode, $starttime, $endtime, $page, $count);
    }

    /**
     * 获取日志详情。
     *
     * @param int $log_id 日志ID。
     * @return array
     */
    public static function getLogDetail($log_id)
    {
        $ErrorLogModel = new ErrorLog();
        $detail = $ErrorLogModel->fetchOne([], ['log_id' => $log_id]);
        if (empty($detail)) {
            YCore::exception(STATUS_ERROR, '日志不存在或已经删除');
        }
        return $detail;
    }
}