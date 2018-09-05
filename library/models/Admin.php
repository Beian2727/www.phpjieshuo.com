<?php
/**
 * 管理员表。
 * @author fingerQin
 * @date 2015-11-17
 */

namespace models;

class Admin extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_admin';

    /**
     * 修改管理员基本信息。
     *
     * @param  int     $adminId      管理员ID。
     * @param  string  $realname     真实姓名。
     * @param  string  $mobilephone  手机号码。
     * @return bool
     */
    public function editInfo($adminId, $realname, $mobilephone)
    {
        $where = [
            'admin_id' => $adminId
        ];
        $data = [
            'realname'    => $realname,
            'mobilephone' => $mobilephone
        ];
        return $this->update($data, $where);
    }

    /**
     * 更改管理员密码。
     *
     * @param  int     $opAdminId  操作此功能的管理员ID。
     * @param  int     $adminId    管理员ID。
     * @param  string  $password   加密后的密码。
     * @param  string  $salt       密码盐。
     * @return bool
     */
    public function editPwd($opAdminId, $adminId, $password, $salt)
    {
        $data = [
            'password'      => $password,
            'modified_time' => date('Y-m-d H:i:s', time()),
            'modified_by'   => $opAdminId,
            'salt'          => $salt
        ];
        $where = [
            'admin_id' => $adminId
        ];
        return $this->update($data, $where);
    }

    /**
     * 获取管理员列表。
     *
     * @param  string  $keyword  查询关键词。（账号、手机、姓名）。
     * @param  int     $page     页码。
     * @param  int     $count    每页显示条数。
     * @return array
     */
    public function getAdminList($keyword = '', $page = 1, $count = 10)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = ' a.admin_id,a.realname,a.username,a.password,a.mobilephone,a.roleid,a.lastlogintime,a.created_time,b.rolename ';
        $where   = ' WHERE a.status = :status ';
        $params  = [
            ':status' => self::STATUS_NORMAL
        ];
        if (strlen($keyword) > 0) {
            $where .= ' AND ( a.realname LIKE :realname OR a.username LIKE :username OR a.mobilephone LIKE :mobilephone )';
            $params[':realname']    = "%{$keyword}%";
            $params[':username']    = "%{$keyword}%";
            $params[':mobilephone'] = "%{$keyword}%";
        }
        $orderBy            = ' ORDER BY a.admin_id DESC ';
        $adminRoleModel     = new AdminRole();
        $adminRoletableName = $adminRoleModel->getTableName();
        $sql       = "SELECT COUNT(1) AS count FROM {$this->tableName} AS a LEFT JOIN {$adminRoletableName} AS b ON(a.roleid=b.roleid) {$where}";
        $countData = $this->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM {$this->tableName} AS a LEFT JOIN {$adminRoletableName} AS b ON(a.roleid=b.roleid) {$where} {$orderBy} LIMIT {$offset},{$count}";
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
