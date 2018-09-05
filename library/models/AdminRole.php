<?php
/**
 * 后台菜单表。
 * @author fingerQin
 * @date 2015-11-18
 */

namespace models;

use common\YCore;

class AdminRole extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_admin_role';

    /**
     * 获取全部角色。
     *
     * @param  bool  $isConvert 是否转换为一维的键值数组。['1' => '管理员', '2' => '普通管理员']
     * @return array
     */
    public function getAllRole($isConvert = false)
    {
        $column = [
            'roleid',
            'rolename',
            'listorder',
            'description',
            'created_time'
        ];
        $where = [
            'status' => self::STATUS_NORMAL
        ];
        $roleList = $this->fetchAll($column, $where);
        $data      = [];
        if ($isConvert) {
            foreach ($roleList as $role) {
                $data[$role['roleid']] = $role['rolename'];
            }
        } else {
            $data = $roleList;
        }
        return $data;
    }

}
