<?php
/**
 * 用户副表。
 * @author fingerQin
 * @date 2016-05-23
 */

namespace models;

class UserData extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_user_data';

    /**
     * 初始化表数据。
     *
     * @param  int     $userid        用户ID。
     * @param  string  $mobilephone   手机号码。
     * @param  string  $realname      真实姓名。
     * @param  string  $nickname      用户昵称。
     * @param  string  $email         邮箱地址。
     * @param  string  $avatar        头像。
     * @param  string  $signature     签名。
     * @return bool
     */
    public function initTableData($userid, $mobilephone = '', $realname = '', $nickname = '', $email = '', $avatar = '', $signature = '')
    {
        $data = [
            'user_id'      => $userid,
            'nickname'     => $nickname,
            'realname'     => $realname,
            'mobilephone'  => $mobilephone,
            'email'        => $email,
            'avatar'       => $avatar,
            'signature'    => $signature,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $id = $this->insert($data);
        return $id > 0 ? true : false;
    }

    /**
     * 修改表数据。
     *
     * @param  int     $userid        用户ID。
     * @param  string  $mobilephone   手机号码。
     * @param  string  $realname      真实姓名。
     * @param  string  $nickname      用户昵称。
     * @param  string  $email         邮箱地址。
     * @param  string  $avatar        头像。
     * @param  string  $signature     签名。
     * @return bool
     */
    public function editInfo($userid, $mobilephone = '', $realname = '', $nickname = '', $email = '', $avatar = '', $signature = '')
    {
        $data = [
            'user_id'       => $userid,
            'nickname'      => $nickname,
            'realname'      => $realname,
            'mobilephone'   => $mobilephone,
            'email'         => $email,
            'avatar'        => $avatar,
            'signature'     => $signature,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $this->update($data, ['user_id' => $userid]);
        return $ok ? true : false;
    }
}