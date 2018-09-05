<?php
/**
 * 用户登录历史表。
 * @author fingerQin
 * @date 2015-11-14
 */

namespace models;

class UserLogin extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_user_login';

    /**
     * 添加登录记录。
     *
     * @param  int      $userid      用户ID。
     * @param  int      $loginTime   登录时间。时间戳。
     * @param  string   $loginIp     登录IP。
     * @param  string   $loginEntry  登录入口。
     * @return bool
     */
    public function addLoginRecord($userid, $loginTime, $loginIp, $loginEntry)
    {
        $data = [
            'user_id'     => $userid,
            'login_ip'    => $loginIp,
            'login_entry' => $loginEntry
        ];
        $insertId = $this->insert($data);
        return $insertId > 0 ? true : false;
    }

    /**
     * 获取用户登录记录。
     *
     * @param  int   $userid       用户ID。
     * @param  int   $startTime    开始时间。时间戳。
     * @param  int   $endTime      结束时间。时间戳。
     * @return array
     */
    public function getUserLoginRecord($userid, $startTime, $endTime)
    {
        $sql    = "SELECT * FROM {$this->tableName} WHERE user_id = :user_id AND login_time BETWEEN :start_time AND :end_time";
        $params = [
            ':user_id'    => $userid,
            ':start_time' => $startTime,
            ':end_time'   => $endTime
        ];
        $sth = $this->dbClient->prepare($sql, $this->prepareAttr);
        $sth->execute($params);
        $list = $sth->fetchAll();
        return $list ? $list : [];
    }
}