<?php
/**
 * 用户公共controller。
 * --1、Yaf框架会根据特有的类名后缀(DbBase、Controller、Plugin)进行自动加载。为避免这种情况请不要以这样的名称结尾。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace common\controllers;

use common\YCore;
use common\YUrl;
use services\UserService;

class User extends Common
{
    /**
     * 用户ID。
     *
     * @var number
     */
    protected $user_id = 0;

    /**
     * 手机号码。
     *
     * @var string
     */
    protected $mobilephone = '';

    /**
     * 用户名。
     *
     * @var string
     */
    protected $username = '';

    /**
     * 是否登录。
     *
     * @var bool
     */
    protected $isLogin = false;

    /**
     * 前置方法
     * -- 1、登录权限判断。
     *
     * @see \common\controllers\Common::init()
     */
    public function init()
    {
        parent::init();
        try {
            $result = UserService::checkAuth($_COOKIE['auth_token']);
            $this->user_id     = $result['user_id'];
            $this->mobilephone = $result['mobilephone'];
            $this->username    = $result['username'];
            $this->isLogin     = true;
        } catch (\Exception $e) {
            if ($this->_request->isXmlHttpRequest()) {
                YCore::exception($e->getCode(), $e->getMessage());
            } else {
                $defaultRedirectUrl = YUrl::getUrl();
                $redirectUrl        = $this->getString('redirect_url', $defaultRedirectUrl);
                $this->redirect(YUrl::createFrontendUrl('Public', 'Login', ['redirect_url' => $redirectUrl]));
            }
        }
        $this->assign('moduleName', $this->_moduleName);
        $this->assign('ctrlName', $this->_ctrlName);
        $this->assign('actionName', $this->_actionName);
        $this->assign('is_login', $this->isLogin);
    }
}