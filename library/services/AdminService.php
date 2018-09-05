<?php
/**
 * 后台管理员。
 * @author fingerQin
 * @date 2015-11-19
 */

namespace services;

use finger\Validator;
use finger\DbBase;
use finger\MobileDetect;
use common\YCore;
use common\YUrl;
use models\Admin;
use models\AdminRole;
use models\Menu;
use models\AdminRolePriv;
use models\AdminLoginHistory;

class AdminService extends AbstractService
{
    /**
     * 获取管理员列表。
     *
     * @param  string $keyword   查询关键词(账号、手机、姓名)。
     * @param  int    $page      当前页码。
     * @param  int    $count     每页显示条数。
     * @return array
     */
    public static function getAdminList($keyword = '', $page, $count)
    {
        $AdminModel     = new Admin();
        $result         = $AdminModel->getAdminList($keyword, $page, $count);
        $AdminRoleModel = new AdminRole();
        foreach ($result['list'] as $key => $item) {
            $where = [
                'roleid' => $item['roleid'], 
                'status' => AdminRole::STATUS_NORMAL
            ];
            $role = $AdminRoleModel->fetchOne([], $where);
            $item['rolename']      = $role['rolename'];
            $item['lastlogintime'] = YCore::formatDateTime($item['lastlogintime']);
            $result['list'][$key]  = $item;
        }
        return $result;
    }

    /**
     * 添加管理员。
     *
     * @param  int     $adminId      管理员ID。 
     * @param  string  $realname     真实姓名。
     * @param  string  $username     账号。
     * @param  string  $password     密码。
     * @param  string  $mobilephone  手机号码。
     * @param  int     $roleid       角色ID。
     * @return void
     */
    public static function addAdmin($adminId, $realname, $username, $password, $mobilephone, $roleid)
    {
        // [1]
        self::checkRealname($realname);
        self::checkUsername($username);
        self::checkPassword($password);
        self::checkMobilephone($mobilephone);

        self::isExistAdmin($username);
        AdminPermissionService::isExistRole($roleid);

        $salt        = YCore::create_randomstr(6);
        $md5Password = self::encryptPassword($password, $salt);
        $data        = [
            'realname'     => $realname,
            'username'     => $username,
            'password'     => $md5Password,
            'mobilephone'  => $mobilephone,
            'salt'         => $salt,
            'roleid'       => $roleid,
            'status'       => Admin::STATUS_NORMAL,
            'created_by'   => $adminId,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $AdminModel = new Admin();
        $status     = $AdminModel->insert($data);
        if (!$status) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 编辑管理员。
     *
     * @param  int     $opAdminId    当前操作此功能的管理员ID。
     * @param  int     $adminId      管理员ID。
     * @param  int     $realname     真实姓名。
     * @param  string  $password     密码。不填则保持原密码。
     * @param  string  $mobilephone  手机号码。
     * @param  int     $roleid       角色ID。
     * @return void
     */
    public static function editAdmin($opAdminId, $adminId, $realname, $mobilephone, $roleid, $password = '')
    {
        // [1]
        self::checkRealname($realname);
        self::checkMobilephone($mobilephone);
        (strlen($password) > 0) && self::checkPassword($password);

        self::isExistAdmin($adminId);
        AdminPermissionService::isExistRole($roleid);

        $data = [
            'realname'      => $realname,
            'mobilephone'   => $mobilephone,
            'roleid'        => $roleid,
            'modified_time' => date('Y-m-d H:i:s', time()),
            'modified_by'   => $opAdminId
        ];
        if (strlen($password) > 0) {
            $salt             = YCore::create_randomstr(6);
            $md5Password      = self::encryptPassword($password, $salt);
            $data['password'] = $md5Password;
            $data['salt']     = $salt;
        }
        $where = [
            'admin_id' => $adminId
        ];
        $AdminModel = new Admin();
        $status     = $AdminModel->update($data, $where);
        if (!$status) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 删除管理员账号。
     * -- 1、超级管理员账号是不允许删除的。
     *
     * @param  int $opAdminId   操作管理员ID。
     * @param  int $adminId     管理员账号ID。
     * @return void
     */
    public static function deleteAdmin($opAdminId, $adminId)
    {
        self::isExistAdmin($adminId);
        if ($adminId == 1) {
            YCore::exception(STATUS_ERROR, '超级管理员账号不能删除');
        }
        $data = [
            'status'        => Admin::STATUS_DELETED,
            'modified_time' => date('Y-m-d H:i:s', time()),
            'modified_by'   => $opAdminId
        ];
        $where = [
            'admin_id' => $adminId
        ];
        $AdminModel = new Admin();
        $ok = $AdminModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 获取管理员账号详情。
     *
     * @param  int   $adminId 管理员账号ID。
     * @return array
     */
    public static function getAdminDetail($adminId)
    {
        $AdminModel  = new Admin();
        $adminDetail = $AdminModel->fetchOne([], ['admin_id' => $adminId, 'status' => Admin::STATUS_NORMAL]);
        if (empty($adminDetail)) {
            YCore::exception(STATUS_ERROR, '管理员账号不存在或已经删除');
        }
        return $adminDetail;
    }

    /**
     * 管理员登录。
     * -- 1、后续增加IP限制与登录错误次数限制。
     *
     * @param  string $username 账号。
     * @param  string $password 密码。
     * @return void
     */
    public static function login($username, $password)
    {
        if (strlen($username) === 0) {
            YCore::exception(STATUS_ERROR, '账号不能为空');
        }
        if (strlen($password) === 0) {
            YCore::exception(STATUS_ERROR, '密码不能为空');
        }
        $AdminModel = new Admin();
        $adminInfo  = $AdminModel->fetchOne([], [
            'username' => $username,
            'status'   => Admin::STATUS_NORMAL
        ]);
        if (empty($adminInfo)) {
            YCore::exception(STATUS_ERROR, '账号不存在');
        }
        $encryptPassword = self::encryptPassword($password, $adminInfo['salt']);
        if ($encryptPassword != $adminInfo['password']) {
            YCore::exception(STATUS_ERROR, '密码不正确');
        }
        self::addAdminLoginHistory($adminInfo['admin_id']);
        $updateData = [
            'lastlogintime' => date('Y-m-d H:i:s', time())
        ];
        $updateWhere = [
            'admin_id' => $adminInfo['admin_id']
        ];
        $AdminModel->update($updateData, $updateWhere);
        $authToken = self::createToken($adminInfo['admin_id'], $encryptPassword);
        self::setAuthTokenLastAccessTime($adminInfo['admin_id'], $authToken, time());
        $adminCookieDomain = YUrl::getDomainName(false);
        setcookie('admin_token', $authToken, 0, '/', $adminCookieDomain);
    }

    /**
     * 添加管理员登录历史。
     *
     * @param  int   $adminId 管理员ID。
     * @return void
     */
    private static function addAdminLoginHistory($adminId)
    {
        $browserType = 'computer';
        $detect      = new MobileDetect();
        if ($detect->isMobile() && !$detect->isTablet()) {
            $browserType = 'phone';
        } else if ($detect->isMobile() && $detect->isTablet()) {
            $browserType = 'tablet';
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $ip        = YCore::ip();
        $address   = '';
        $AdminLoginHistoryModel = new AdminLoginHistory();
        $data = [
            'admin_id'     => $adminId,
            'user_agent'   => $userAgent,
            'ip'           => $ip,
            'browser_type' => $browserType,
            'address'      => $address,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $AdminLoginHistoryModel->insert($data);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 获取管理员登录记录。
     *
     * @param  int   $adminId  管理员ID。
     * @param  int   $page     当前页码。
     * @param  int   $count    每页显示条数。
     * @return array
     */
    public static function getAdminLoginHistoryList($adminId = -1, $page = 1, $count = 20)
    {
        $offset    = self::getPaginationOffset($page, $count);
        $fromTable = ' FROM ms_admin_login_history AS a LEFT JOIN ms_admin AS b ON(a.admin_id = b.admin_id) ';
        $columns   = ' b.admin_id,b.realname,b.username,b.mobilephone,a.created_time,a.browser_type,a.ip,a.address ';
        $where     = ' WHERE 1 = 1 ';
        $params    = [];
        if ($adminId != - 1) {
            $where .= ' AND a.admin_id = :admin_id ';
            $params[':admin_id'] = $adminId;
        }
        $defaultDb  = new DbBase();
        $orderBy    = ' ORDER BY a.id DESC ';
        $sql        = "SELECT COUNT(1) AS count {$fromTable} {$where}";
        $countData  = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total      = $countData ? $countData['count'] : 0;
        $sql        = "SELECT {$columns} {$fromTable} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list       = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        $result     = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => self::IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 修改密码。
     *
     * @param  int     $adminId   用户ID。
     * @param  string  $oldPwd    旧密码。
     * @param  string  $newPwd    新密码。
     * @return void
     */
    public static function editPwd($adminId, $oldPwd, $newPwd)
    {
        if (strlen($oldPwd) === 0) {
            YCore::exception(STATUS_ERROR, '旧密码必须填写');
        }
        if (strlen($newPwd) === 0) {
            YCore::exception(STATUS_ERROR, '新密码必须填写');
        }
        $AdminModel = new Admin();
        $adminInfo  = $AdminModel->fetchOne([], ['admin_id' => $adminId]);
        if (empty($adminInfo) || $adminInfo['status'] != Admin::STATUS_NORMAL) {
            YCore::exception(STATUS_ERROR, '管理员不存在或已经删除');
        }
        if (!Validator::is_len($newPwd, 6, 20, true)) {
            YCore::exception(STATUS_ERROR, '新密码长度必须6~20之间');
        }
        if (!Validator::is_alpha_dash($newPwd)) {
            YCore::exception(STATUS_ERROR, '新密码格式不正确');
        }
        $oldPwdEncrypt = self::encryptPassword($oldPwd, $adminInfo['salt']);
        if ($oldPwdEncrypt != $adminInfo['password']) {
            YCore::exception(STATUS_ERROR, '旧密码不正确!');
        }
        $salt            = YCore::create_randomstr(6);
        $encryptPassword = self::encryptPassword($newPwd, $salt);
        $ok              = $AdminModel->editPwd($adminId, $adminId, $encryptPassword, $salt);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '密码修改失败');
        }
    }

    /**
     * 获取管理员详情。
     *
     * @param  int   $adminId 管理员ID。
     * @return array
     */
    public static function getAdminInfo($adminId)
    {
        $AdminModel = new Admin();
        $data = $AdminModel->fetchOne([], [
            'admin_id' => $adminId,
            'status'   => Admin::STATUS_NORMAL
        ]);
        if (empty($data)) {
            YCore::exception(STATUS_ERROR, '管理员不存在或已经删除');
        }
        return $data;
    }

    /**
     * 管理员修改自己的资料。
     *
     * @param  int     $adminId       管理员ID。
     * @param  string  $realname      真实姓名。
     * @param  string  $mobilephone   手机号码。
     * @return void
     */
    public static function editInfo($adminId, $realname, $mobilephone)
    {
        self::checkRealname($realname);
        self::checkMobilephone($mobilephone);
        $AdminModel = new Admin();
        $ok = $AdminModel->editInfo($adminId, $realname, $mobilephone);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '修改失败');
        }
    }

    /**
     * 退出登录。
     * 
     * @return void
     */
    public static function logout()
    {
        $AdminCookieDomain = YUrl::getDomainName(false);
        $validTime = time() - 3600;
        setcookie('admin_token', '', $validTime, '/', $AdminCookieDomain);
    }

    /**
     * 检查用户权限。
     * -- 1、在每次用户访问程序的时候调用。
     *
     * @param  string  $moduleName   模块名称。
     * @param  string  $ctrlName     控制器名称。
     * @param  string  $actionName   操作名称。
     * 
     * @return array 基本信息。
     */
    public static function checkAuth($moduleName, $ctrlName, $actionName)
    {
        // [1] token解析
        $token       = isset($_COOKIE['admin_token']) ? $_COOKIE['admin_token'] : '';
        $tokenParams = self::parseToken($token);
        $adminId     = $tokenParams['admin_id'];
        $password    = $tokenParams['password'];
        $accessTime  = time();
        // [2] 用户存在与否判断
        $AdminModel = new Admin();
        $adminInfo  = $AdminModel->fetchOne([], [
            'admin_id' => $adminId,
            'status'   => Admin::STATUS_NORMAL
        ]);
        if (empty($adminInfo)) {
            self::logout();
            YCore::exception(STATUS_ERROR, '账号不存在或已经被禁用');
        }
        if ($password != $adminInfo['password']) {
            self::logout();
            YCore::exception(STATUS_ERROR, '您的密码被修改,请重新登录');
        }
        // [3] token是否赶出了超时时限
        $cache         = YCore::getCache();
        $cacheKeyToken = "admin_token_key_{$adminId}";
        $cacheKeyTime  = "admin_access_time_key_{$adminId}";
        $cacheToken    = $cache->get($cacheKeyToken);
        if ($cacheToken === false) {
            self::logout();
            YCore::exception(ADMIN_STATUS_NOT_LOGIN, '您还没有登录');
        }
        if ($cacheToken === null) {
            self::logout();
            YCore::exception(ADMIN_STATUS_LOGIN_TIMEOUT, '登录超时,请重新登录');
        }
        if ($cacheToken != $token) {
            self::logout();
            YCore::exception(ADMIN_STATUS_OTHER_LOGIN, '您的账号已在其他地方登录');
        }
        // 只有能正常登录管理后台，默认都拥有 Index 模块的权限。
        if (strtolower($ctrlName) != 'index') {
            $ok = self::checkMenuPower($adminInfo['roleid'], $moduleName, $ctrlName, $actionName);
            if (!$ok) {
                YCore::exception(ADMIN_STATUS_FORBIDDEN, '您没有权限执行此操作');
            }
        }
        self::setAuthTokenLastAccessTime($adminId, $token, $accessTime);
        $data = [
            'admin_id'    => $adminInfo['admin_id'],
            'realname'    => $adminInfo['realname'],
            'username'    => $adminInfo['username'],
            'mobilephone' => $adminInfo['mobilephone'],
            'roleid'      => $adminInfo['roleid']
        ];
        return $data;
    }

    /**
     * 检查指定角色的菜单权限。
     *
     * @param  int     $roleid      角色ID。
     * @param  string  $moduleName  模块名称。
     * @param  string  $ctrlName    控制器名称。
     * @param  string  $actionName  操作名称。
     * @return bool
     */
    private static function checkMenuPower($roleid, $moduleName, $ctrlName, $actionName)
    {
        if ($roleid == 1) {
            return true; // 超级管理员组拥有绝对的权限。
        }
        $MenuModel = new Menu();
        $where = [
            'c' => $ctrlName,
            'a' => $actionName
        ];
        $menuInfo = $MenuModel->fetchOne([], $where);
        if (empty($menuInfo)) {
            return false;
        }
        $where = [
            'roleid'  => $roleid,
            'menu_id' => $menuInfo['menu_id']
        ];
        $AdminRolePrivModel = new AdminRolePriv();
        $priv = $AdminRolePrivModel->fetchOne([], $where);
        if (empty($priv)) {
            return false;
        }
        return true;
    }

    /**
     * 设置 auth_token 最后的访问时间。
     *
     * @param  int    $adminId      管理员ID。
     * @param  string $authToken    auth_token。
     * @param  int    $accessTime   最后访问时间戳。
     * @return void
     */
    private static function setAuthTokenLastAccessTime($adminId, $authToken, $accessTime)
    {
        $cache         = YCore::getCache();
        $cacheTime     = YCore::config('admin_logout_time') * 60;
        $cacheKeyToken = "admin_token_key_{$adminId}"; // 用户保存auth_token的缓存键。
        $cacheKeyTime  = "admin_access_time_key_{$adminId}"; // 用户保存最后访问时间的缓存键。
        $cache->set($cacheKeyToken, $authToken, $accessTime);
        $cache->set($cacheKeyTime, $accessTime, $accessTime);
    }

    /**
     * 加密密码。
     *
     * @param  string $password     密码明文。
     * @param  string $salt         密码加密盐。
     * @return string
     */
    private static function encryptPassword($password, $salt)
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 生成Token。
     * -- 1、token只分接口与非接口两种模式。
     *
     * @param  int    $adminId      管理员ID。
     * @param  string $password     用户表password字段。
     * @return string
     */
    private static function createToken($adminId, $password)
    {
        $str = "{$adminId}\t{$password}";
        return YCore::sys_auth($str, 'ENCODE', '', 0);
    }

    /**
     * 解析Token。
     *
     * @param  string $token token会话。
     * @return array
     */
    private static function parseToken($token)
    {
        $data = YCore::sys_auth($token, 'DECODE');
        $data = explode("\t", $data);
        if (count($data) != 2) {
            YCore::exception(ADMIN_STATUS_LOGIN_TIMEOUT, '登录超时,请重新登录');
        }
        $result = [
            'admin_id' => $data[0],  // 用户ID。
            'password' => $data[1]
        ]; // 加密的密码。
        return $result;
    }

    /**
     * 检查管理员账号格式。
     * @param  string $username 管理员账号。
     * @return void
     */
    public static function checkUsername($username)
    {
        $data = [
            'username' => $username
        ];
        $rules = [
            'username' => '账号|require|alpha_dash|len:6:20:0'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查管理员密码格式。
     * @param  string $password 密码。
     * @return void
     */
    public static function checkPassword($password)
    {
        $data = [
            'password' => $password
        ];
        $rules = [
            'password' => '密码|require|alpha_dash|len:6:20:0'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查手机号格式。
     * @param  string $mobilephone 管理员手机号码。
     * @return void
     */
    public static function checkMobilephone($mobilephone)
    {
        $data = [
            'mobilephone' => $mobilephone
        ];
        $rules = [
            'mobilephone' => '手机号码|require|mobilephone'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查真实姓名格式。
     * @param  string $realname 管理员真实姓名。
     * @return void
     */
    public static function checkRealname($realname)
    {
        $data = [
            'realname' => $realname
        ];
        $rules = [
            'realname' => '真实姓名|require|len:2:20:1'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查管理员账号是否存在。
     * @param  string  $username 管理员账号。
     * @param  string  $errMsg   自定义错误信息。
     * @return array
     */
    public static function isExistAdmin($username, $errMsg = '')
    {
        $AdminModel  = new Admin();
        $adminDetail = $AdminModel->fetchOne([], ['username' => $username, 'status' => Admin::STATUS_NORMAL]);
        if (!empty($adminDetail)) {
            $errMsg = (strlen($errMsg) > 0) ? $errMsg : '管理员账号已经存在';
            YCore::exception(STATUS_ERROR, $errMsg);
        }
        return $adminDetail;
    }
}