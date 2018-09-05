<?php
/**
 * 账号管理。
 * @author fingerQin
 * @date 2018-01-11
 */

use common\YUrl;
use services\UserService;

class UserController extends \common\controllers\User
{
    /**
     * 密码修改。
     */
    public function editPwdAction()
    {
        if ($this->_request->isPost()) {
            $oldPwd = $this->getString('oldPwd', '');
            $newPwd = $this->getString('newPwd', '');
            UserService::editPwd($this->user_id, $oldPwd, $newPwd);
            $url = YUrl::createFrontendUrl('Index', 'index');
            $this->success('修改成功', $url, 1);
        }
    }
}