<?php
/**
 * 用户黑名单表模型。
 * @author fingerQin
 * @date 2015-11-05
 */

namespace models;

use common\YCore;

class UserBlacklist extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_user_blacklist';

    /**
     * 判断用户是否被封禁。
     *
     * @param int $user_id 用户ID。
     * @return array
     * [
     *     'status'  => 0或1,1被封禁。0未封禁。
     *     'message' => '封禁数据。如：您已经被永久封禁。',
     * ]
     */
    public function isForbidden($userid)
    {
        $retData = [
            'status'  => self::STATUS_INVALID,
            'message' => '正常使用'
        ];
        $result = $this->fetchOne([], ['user_id' => $userid, 'status'  => 1]);
        if (empty($result)) {
            return $retData; // 没有封禁记录。
        }

        if ($result['ban_type'] == 1) {
            $retData['status']  = self::STATUS_NORMAL;
            $retData['message'] = '您的账号已经被永久封禁';
            return $retData;
        }

        $current_timestamp = date('Y-m-d H:i:s', time());
        if ($result['ban_type'] == 2 && ($result['ban_end_time'] < $current_timestamp || $result['ban_start_time'] > $current_timestamp)) {
            return $retData; // 过了封禁时间限制。
        } else {
            $banDate            = $result['ban_end_time'];
            $retData['status']  = self::STATUS_NORMAL;
            $retData['message'] = "您当前被禁止登录。解禁日期：{$banDate}";
            return $retData;
        }
    }

    /**
     * 封禁账号。
     *
     * @param  int      $adminId        管理员ID。
     * @param  int      $userid         用户ID。
     * @param  string   $username       用户账号。
     * @param  int      $banType        封禁类型。1永久封禁、2临时封禁。
     * @param  int      $banStartTime   封禁开始时间。
     * @param  int      $banEndTime     封禁截止时间。
     * @param  string   $banReason      账号封禁原因。
     * @return bool
     */
    public function forbiddenUser($adminId, $userid, $username, $banType, $banStartTime = 0, $banEndTime = 0, $banReason = '')
    {
        if ($ban_type == 1) { // 永久封禁不需要设置时间。
            $banStartTime = '';
            $banEndTime   = '';
        } else {
            if (strlen($banStartTime) == 0) {
                YCore::exception(-1, 'The ban_start_time parameters is wrong');
            }
            if (strlen($banEndTime) == 0) {
                YCore::exception(-1, 'The ban_end_time parameters is wrong');
            }
        }
        $data = [
            'user_id'        => $userid,
            'username'       => $username,
            'ban_type'       => $banType,
            'ban_start_time' => $banStartTime,
            'ban_end_time'   => $banEndTime,
            'ban_reason'     => $banReason,
            'created_by'     => $adminId,
            'created_time'   => date('Y-m-d H:i:s', time()),
            'status'         => self::STATUS_NORMAL
        ];
        $insertId = $this->insert($data);
        if ($insertId > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 解禁账号。
     *
     * @param  int  $userid  用户ID。
     * @param  int  $adminId 管理员ID。
     * @return bool
     */
    public function unforbiddenUser($userid, $adminId)
    {
        $data = $this->fetchOne([], ['user_id' => $userid, 'status' => 1]);
        if ($data) {
            $updateData = [
                'status'        => self::STATUS_INVALID,
                'modified_by'   => $adminId,
                'modified_time' => date('Y-m-d H:i:s', time())
            ];
            return $this->update($updateData, ['id' => $data['id']]);
        } else {
            return false;
        }
    }

}
