<?php
/**
 * 公共页面。
 * @author fingerQin
 * @date 2016-09-16
 */

use common\YUrl;
use services\UserService;

class PublicController extends \common\controllers\Guest
{
    /**
     * 用户登录页。
     */
    public function loginAction()
    {
        if ($this->_request->isPost()) {
            $username = $this->getString('username', '');
            $password = $this->getString('password', '');
            UserService::login($username, $password);
            $url = YUrl::createFrontendUrl('Index', 'index');
            $this->success('登录成功', $url, 1);
        }
    }

    /**
     * 用户退出。
     */
    public function logoutAction()
    {
        UserService::logout();
        $url = YUrl::createFrontendUrl('Public', 'login');
        $this->success('退出成功', $url, 1);
    }
}