<?php
use common\YUrl;
use services\UserService;
$token   = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : '';
$userid  = UserService::getTokenUserId($token);
$isLogin =  $userid > 0 ? true : false;
?>
<style>
.userStatus {
    float:right;
    position: absolute;
    top: 0;
    right: 0;
    color: #5FB878
}
.userStatus a {
    color: #FFFFFF;
    padding: 0 20px;
    color: rgba(255,255,255,.7);
    transition: all .3s;
    -webkit-transition: all .3s;
    font-size: 14px;
    line-height: 64px;
}
</style>
<?php if ($isLogin): ?>
<div class="userStatus">
    <a href="<?php echo YUrl::createFrontendUrl('User', 'editPwd'); ?>">修改密码</a>|
    <a href="<?php echo YUrl::createFrontendUrl('Public', 'logout'); ?>">退出登录</a>
</div>
<?php endif; ?>