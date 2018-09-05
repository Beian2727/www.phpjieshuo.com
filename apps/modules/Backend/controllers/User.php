<?php
/**
 * 用户管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use finger\Paginator;
use common\YCore;
use services\UserService;

class UserController extends \common\controllers\Admin
{
    /**
     * 用户列表。
     */
    public function indexAction()
    {
        $username    = $this->getString('username', '');
        $mobilephone = $this->getString('mobilephone', '');
        $starttime   = $this->getString('starttime', '');
        $endtime     = $this->getString('endtime', '');
        $isVerify    = $this->getString('is_verify', -1);
        $page        = $this->getInt(YCore::appconfig('pager'), 1);
        $list        = UserService::getUserList($username, $mobilephone, $isVerify, $starttime, $endtime, $page, 20);
        $paginator   = new Paginator($list['total'], 20);
        $pageHtml    = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $list['list']);
        $this->assign('mobilephone', $mobilephone);
        $this->assign('username', $username);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        $this->assign('is_verify', $isVerify);
    }

    /**
     * 用户添加。
     */
    public function addAction() {
        if ($this->_request->isXmlHttpRequest()) {
            $account     = $this->getString('account', '');
            $username    = $this->getString('username');
            $password    = $this->getString('password');
            $mobilephone = $this->getString('mobilephone', '');
            $email       = $this->getString('email', '');
            $realname    = $this->getString('realname', '');
            $avatar      = $this->getString('avatar', '');
            $signature   = $this->getString('signature', '');
            UserService::addUser($username, $password, $mobilephone, $email, $realname, $avatar, $signature);
            $this->json(true, '添加成功');
            
        }
    }

    /**
     * 用户编辑。
     */
    public function editAction() {
        if ($this->_request->isXmlHttpRequest()) {
            $userid      = $this->getInt('user_id');
            $username    = $this->getString('username');
            $password    = $this->getString('password', ''); // 传空字符串代表保持原密码。
            $mobilephone = $this->getString('mobilephone', '');
            $email       = $this->getString('email', '');
            $realname    = $this->getString('realname', '');
            $avatar      = $this->getString('avatar', '');
            $signature   = $this->getString('signature', '');
            UserService::editUser($userid, $username, $password, $mobilephone, $email, $realname, $avatar, $signature);
            $this->json(true, '操作成功');
        }
        $userid   = $this->getInt('user_id');
        $userinfo = UserService::getUserDetail($userid);
        $this->assign('userinfo', $userinfo);
    }

    /**
     * 封禁用户。
     */
    public function forbidAction() {
        if ($this->_request->isXmlHttpRequest()) {
            $userid       = $this->getInt('user_id');
            $banType      = $this->getInt('ban_type');
            $banStartTime = $this->getString('ban_start_time');
            $banEndTime   = $this->getString('ban_end_time');
            $banReason    = $this->getString('ban_reason');
            UserService::addBlacklist($this->admin_id, $userid, $banType, $banStartTime, $banEndTime, $banReason);
            $this->json(true, '封禁成功');
        }
        $userid = $this->getInt('user_id');
        $this->assign('user_id', $userid);
    }

    /**
     * 解禁用户。
     */
    public function unforbidAction() {
        if ($this->_request->isXmlHttpRequest()) {
            $userid = $this->getInt('user_id');
            UserService::unforbid($userid, $this->admin_id);
            $this->json(true, '解禁成功');
        }
    }

    /**
     * 查看用户详情。
     */
    public function viewAction() {
        $userid   = $this->getInt('user_id');
        $userinfo = UserService::getUserDetail($userid);
        $this->assign('userinfo', $userinfo);
    }
}