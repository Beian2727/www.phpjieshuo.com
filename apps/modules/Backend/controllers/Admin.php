<?php
/**
 * 管理员管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use services\AdminService;
use finger\Paginator;
use services\AdminPermissionService;
use common\YCore;

class AdminController extends \common\controllers\Admin
{

    /**
     * 管理员列表。
     */
    public function indexAction()
    {
        $keywords  = $this->getString('keywords', '');
        $page      = $this->getString('page', 1);
        $result    = AdminService::getAdminList($keywords, $page, 10);
        $paginator = new Paginator($result['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('keywords', $keywords);
        $this->assign('list', $result['list']);
    }

    /**
     * 添加管理员。
     */
    public function addAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $realname    = $this->getString('realname');
            $username    = $this->getString('username');
            $password    = $this->getString('password');
            $mobilephone = $this->getString('mobilephone');
            $roleid      = $this->getInt('roleid');
            AdminService::addAdmin($this->admin_id, $realname, $username, $password, $mobilephone, $roleid);
            $this->json(true, '添加成功');
        }
        $roleList = AdminPermissionService::getRoleList();
        $this->assign('role_list', $roleList);
    }

    /**
     * 编辑管理员。
     */
    public function editAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $adminId     = $this->getInt('admin_id');
            $realname    = $this->getString('realname');
            $password    = $this->getString('password', '');
            $mobilephone = $this->getString('mobilephone');
            $roleid      = $this->getInt('roleid');
            AdminService::editAdmin($this->admin_id, $adminId, $realname, $mobilephone, $roleid, $password);
            $this->json(true, '修改成功');
        }
        $adminId     = $this->getInt('admin_id');
        $adminDetail = AdminService::getAdminDetail($adminId);
        $roleList    = AdminPermissionService::getRoleList();
        $this->assign('detail', $adminDetail);
        $this->assign('role_list', $roleList);
    }

    /**
     * 删除管理员。
     */
    public function deleteAction()
    {
        $adminId = $this->getInt('admin_id');
        AdminService::deleteAdmin($this->admin_id, $adminId);
        $this->json(true, '删除成功');
        $this->end();
    }

    /**
     * 管理员修改个人密码。
     */
    public function editPwdAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $oldPwd = $this->getString('old_pwd');
            $newPwd = $this->getString('new_pwd');
            AdminService::editPwd($this->admin_id, $oldPwd, $newPwd);
            $this->json(true, '修改成功');
        }
        $adminInfo = AdminService::getAdminInfo($this->admin_id);
        $this->assign('admin_info', $adminInfo);
    }

    /**
     * 登录历史。
     */
    public function loginHistoryAction()
    {
        $page      = $this->getString(YCore::appconfig('pager'), 1);
        $result    = AdminService::getAdminLoginHistoryList($this->admin_id, $page, 20);
        $paginator = new Paginator($result['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $result['list']);
    }
}
