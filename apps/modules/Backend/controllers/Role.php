<?php
/**
 * 角色管理。
 *
 * @author fingerQin
 * @date 2015-11-26
 */

use services\AdminPermissionService;

class RoleController extends \common\controllers\Admin
{
    /**
     * 角色列表。
     */
    public function indexAction()
    {
        $roleList = AdminPermissionService::getRoleList();
        $this->assign('list', $roleList);
    }

    /**
     * Ajax方式获取角色列表。
     */
    public function ajaxRoleListAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $roleList = AdminPermissionService::getRoleList();
            $this->json(true, 'ok', $roleList);
        }
        $this->end();
    }

    /**
     * 添加角色。
     */
    public function addAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $rolename    = $this->getString('rolename');
            $listorder   = $this->getInt('listorder');
            $description = $this->getString('description');
            AdminPermissionService::addRole($rolename, $listorder, $description);
            $this->json(true, '添加成功');
        }
    }

    /**
     * 编辑角色。
     */
    public function editAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $roleid      = $this->getInt('roleid');
            $rolename    = $this->getString('rolename');
            $listorder   = $this->getInt('listorder');
            $description = $this->getString('description');
            AdminPermissionService::editRole($roleid, $rolename, $listorder, $description);
            $this->json(true, '修改成功');
        }
        $roleid = $this->getInt('roleid');
        $role = AdminPermissionService::getRoleDetail($roleid);
        $this->assign('role', $role);
    }

    /**
     * 删除角色。
     */
    public function deleteAction()
    {
        $roleid = $this->getInt('roleid');
        AdminPermissionService::deleteRole($roleid);
        $this->json(true, '删除成功');
        $this->end();
    }

    /**
     * 设置角色权限。
     */
    public function setPermissionAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $roleid    = $this->getInt('roleid');
            $arrMenuId = $this->getArray('menuid');
            AdminPermissionService::setRolePermission($roleid, $arrMenuId);
            $this->json(true, '设置成功');
        }
    }

    /**
     * 获取角色权限的菜单ID。
     */
    public function getRolePermissionMenuAction()
    {
        $roleid       = $this->getInt('roleid');
        $privMenuList = AdminPermissionService::getRolePermissionMenu($roleid);
        $list         = AdminPermissionService::getMenuList(0);
        $this->assign('list', $list);
        $this->assign('roleid', $roleid);
        $this->assign('priv_menu_list', $privMenuList);
    }
}