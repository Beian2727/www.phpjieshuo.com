<?php
/**
 * 角色权限表。
 * @author fingerQin
 * @date 2015-11-17
 */

namespace models;

class AdminRolePriv extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_admin_role_priv';

    /**
     * 清空角色所有权限数据。
     *
     * @param int $roleid 角色ID。
     * @return bool
     */
    public function clearRolePriv($roleid)
    {
        $where = [
            'roleid' => $roleid
        ];
        return $this->delete($where);
    }

    /**
     * 获取角色全部的权限。
     *
     * @param int $roleid @reutrn void
     */
    public function getRolePriv($roleid)
    {
        $where = [
            'roleid' => $roleid
        ];
        return $this->fetchAll([], $where, 0);
    }

    /**
     * 添加角色权限。
     *
     * @param  int      $roleid  角色ID。
     * @param  string   $menuId  菜单ID。
     * @return bool
     */
    public function addRolePriv($roleid, $menuId)
    {
        $sdata = [
            'roleid'  => $roleid,
            'menu_id' => $menuId
        ];
        $id = $this->insert($sdata);
        return $id ? true : false;
    }

}
