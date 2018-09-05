<?php
/**
 * 用户业务封装。
 * @author fingerQin
 * @date 2015-10-30
 */

namespace services;

use finger\Validator;
use finger\DbBase;
use common\YUrl;
use common\YCore;
use models\UserBlacklist;
use models\UserData;
use models\User;
use models\UserLogin;
use models\UserBind;
use models\FindPwd;
use services\GoldService;

class UserService extends AbstractService
{
    /**
     * 账号类型。
     */
    const ACCOUNT_TYPE_USERNAME = 'username';       // 用户名类型。
    const ACCOUNT_TYPE_PHONE    = 'mobilephone';    // 手机号码类型。
    const ACCOUNT_TYPE_EMAIL    = 'email';          // 邮箱类型。

    /**
     * 手机注册。
     *
     * @param  string  $mobilephone  手机号码。
     * @param  string  $password     密码。
     * @param  string  $code         验证码。
     * 
     * @return int 返回用户ID。
     */
    public static function register($mobilephone, $password, $code)
    {
        // [1] 验证
        self::checkMobilephone($mobilephone);
        self::checkPassword($password);
        self::checkCaptcha($code);
        self::isExistMobilephone($mobilephone);
        SmsService::valiCode(SmsService::SMS_TYPE_REGISTER, $mobilephone, $code);
        // [2]
        $dbBase     = new DbBase();
        $dbBase->beginTransaction();
        $userModel  = new User();
        $salt       = YCore::create_randomstr(6);
        $password   = self::encryptPassword($password, $salt);
        $insertData = [
            'username'         => uniqid('mp'),
            'password'         => $password,
            'salt'             => $salt,
            'mobilephone'      => $mobilephone,
            'mobilephone_ok'   => 1,
            'mobilephone_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            'created_time'     => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
        ];
        $userId = $userModel->insert($insertData);
        if ($userId == 0) {
            $dbBase->rollBack();
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        // [3]
        $userDataModel = new UserData();
        $ok = $userDataModel->initTableData($userId, $mobilephone);
        if (!$ok) {
            $dbBase->rollBack();
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        $dbBase->commit();
        return $userId;
    }

    /**
     * 退出登录。
     * -- 1、只有触屏版、PC版才需要调用这个方法来退出登录。
     *
     * @return bool
     */
    public static function logout()
    {
        $token = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : '';
        if (strlen($token) === 0) {
            return true;
        }
        try {
            $userinfo = self::parseToken($token);
        } catch (\Exception $e) {
            return true;
        }
        $userId = $userinfo['user_id'];
        $ok = self::kick($userId);
        if (!$ok) {
            YCore::exception(STATUS_ERROR, '退出登录失败');
        }
        return true;
    }

    /**
     * 用户登录[用户名、手机、邮箱]。
     * -- 1、登录模式决定了通过哪一种方式管理权限token。
     * -- 2、接口模式是通过返回token进行会话。WEB模式是通过cookie管理。
     *
     * @param  string  $username    账号。支持用户名、手机、邮箱混登。
     * @param  string  $password    密码。
     * @param  bool    $loginEntry  登录入口。1:pc、2:app、3:wap
     * 
     * @return array
     */
    public static function login($username, $password, $loginEntry = 1)
    {
        // [1] 验证
        self::checkPassword($password); 
        
        if (Validator::is_mobilephone($username)) {
            $accountType= self::ACCOUNT_TYPE_PHONE;
            $where = [
                'mobilephone' => $username
            ];
        } else if (Validator::is_email($username)) {
            $accountType= self::ACCOUNT_TYPE_EMAIL;
            $where = [
                'email' => $username
            ];
        } else {
            self::checkUsername($username);
            $accountType= self::ACCOUNT_TYPE_USERNAME;
            $where = [
                'username' => $username
            ];
        }

        // [2] 检测账号密码是否正确。
        $UserModel = new User();
        $userinfo  = $UserModel->fetchOne([], $where);
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '账号与密码不匹配');
        }
        $encryptPwd = self::encryptPassword($password, $userinfo['salt']);
        if ($encryptPwd != $userinfo['password']) {
            YCore::exception(STATUS_ERROR, '账号与密码不匹配');
        }
        $UserBlackModel = new UserBlacklist();
        $forbidInfo     = $UserBlackModel->isForbidden($userinfo['user_id']);
        if ($forbidInfo['status']) {
            YCore::exception(STATUS_ERROR, $forbidInfo['message']);
        }
        $loginTime = date('Y-m-d H:i:s', time());
        $loginIp   = YCore::ip();
        // [3] 记录登录历史。
        $UserLoginModel = new UserLogin();
        $UserLoginModel->addLoginRecord($userinfo['user_id'], $loginTime, $loginIp, $loginEntry);
        // [4] 根据登录入口不同设置不同的token模式。
        $loginModel = 0;
        switch ($loginEntry) {
            case 1: // pc
            case 3: // wap
                $loginModel = 1;
                break;
            case 2: // app
                $loginModel = 2;
                break;
            default :
                YCore::exception(STATUS_ERROR, "Parameter loginEntry is wrong");
                break;
        }
        $authToken  = self::createToken($userinfo['user_id'], $userinfo['password'], $loginTime, $loginModel);
        $returnData = [];
        if ($loginModel == 1) { // web模式。
            $userAuthCookieDomainName = YUrl::getDomainName(false);
            setcookie('auth_token', $authToken, time()+365*86400, '/', $userAuthCookieDomainName);
        } else if ($loginModel == 2) { // 接口模式。
            $returnData['token'] = $authToken;
        } else {
            YCore::exception(STATUS_ERROR, "Parameter login_mode is wrong");
        }
        // [5] 设置token最后被访问的时间。通过这个可以知道用户是否超时。
        self::setAuthTokenLastAccessTime($userinfo['user_id'], $authToken, $loginTime, $loginModel, $loginTime);
        return $returnData;
    }

    /**
     * 获取用户列表。
     *
     * @param  string  $username             账号。
     * @param  string  $mobilephone          手机号。
     * @param  int     $isVerifyMobilephone  手机号是否验证。-1全部、1通过、0未验证。
     * @param  string  $starttime            开始注册时间。
     * @param  string  $endtime              截止注册时间。
     * @param  int     $page                 当前页码。
     * @param  int     $count                每页显示条数。
     * @return array
     */
    public static function getUserList($username = '', $mobilephone = '', $isVerifyMobilephone = -1, $starttime = '', $endtime = '', $page = 1, $count = 20)
    {
        if (strlen($starttime) > 0 && !Validator::is_date($starttime)) {
            YCore::exception(STATUS_ERROR, '开始注册时间格式不对');
        }
        if (strlen($endtime) > 0 && !Validator::is_date($endtime)) {
            YCore::exception(STATUS_ERROR, '截止注册时间格式不对');
        }
        if (strlen($mobilephone) > 0) {
            self::checkMobilephone($mobilephone);
        }
        $from    = 'FROM ms_user AS a LEFT JOIN ms_user_data AS b ON(a.user_id = b.user_id) ';
        $offset  = self::getPaginationOffset($page, $count);
        $columns = ' a.*,b.avatar ';
        $where   = ' WHERE 1 ';
        $params  = [];
        if (strlen($username) > 0) {
            $where .= ' AND username LIKE :username ';
            $params[':username'] = "{$username}%"; // 为了性能，以及常规查询并不会查后缀。
        }
        if (strlen($mobilephone) > 0) {
            $where .= ' AND mobilephone = :mobilephone ';
            $params[':mobilephone'] = $mobilephone;
        }
        if ($isVerifyMobilephone != -1) {
            $where .= ' AND is_verify_mobilephone = :is_verify_mobilephone ';
            $params[':is_verify_mobilephone'] = $isVerifyMobilephone;
        }
        if (strlen($starttime) > 0) {
            $where .= ' AND created_time > :starttime ';
            $params[':starttime'] = $starttime;
        }
        if (strlen($endtime) > 0) {
            $where .= ' AND created_time < :endtime ';
            $params[':endtime'] = $endtime;
        }
        $defaultDb = new DbBase();
        $orderBy   = ' ORDER BY user_id ASC ';
        $sql       = "SELECT COUNT(1) AS count {$from} {$where}";
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} {$from} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        $usersBlacklistModel = new UserBlacklist();
        foreach ($list as $k => $val) {
            // 是否封禁。
            $forbidInfo = $usersBlacklistModel->isForbidden($val['user_id']);
            $val['forbin_status']    = $forbidInfo['status'];
            $val['forbin_label']     = $forbidInfo['message'];
            $val['created_time']     = YCore::formatDateTime($val['created_time']);
            $val['last_login_time']  = YCore::formatDateTime($val['last_login_time']);
            $val['email_time']       = YCore::formatDateTime($val['email_time']);
            $val['mobilephone_time'] = YCore::formatDateTime($val['mobilephone_time']);
            $list[$k]                = $val;
        }
        $result = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => self::IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 添加用户。
     *
     * @param  string  $username     用户名。
     * @param  string  $password     密码。
     * @param  string  $mobilephone  手机号。
     * @param  string  $email        邮箱地址。
     * @param  string  $realname     真实姓名。
     * @param  string  $avatar       头像。
     * @param  string  $signature    签名。
     * @return bool
     */
    public static function addUser($username, $password, $mobilephone = '', $email = '', $realname = '', $avatar = '', $signature = '')
    {
        self::checkUsername($username);
        self::checkPassword($password);
        (strlen($realname) > 0) && self::checkRealname($realname);
        (strlen($signature) > 0) && self::checkSignature($signature);
        (strlen($avatar) > 0) && self::checkAvatar($avatar);

        self::isExistUsername($username);
        if (strlen($mobilephone) > 0) {
            self::checkMobilephone($mobilephone);
            self::isExistMobilephone($mobilephone);
        }
        if (strlen($email) > 0) {
            self::checkEmail($email);
            self::isExistEmail($email);
        }

        $datetime   = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $dbBase     = new DbBase();
        $dbBase->beginTransaction();
        $usersModel = new User();
        $salt       = YCore::create_randomstr(6);
        $password   = self::encryptPassword($password, $salt);
        $data = [
            'username'        => $username,
            'salt'            => $salt,
            'password'        => $password,
            'mobilephone'     => $mobilephone,
            'email'           => $email,
            'last_login_time' => $datetime,
            'created_time'    => $datetime,
            'modified_time'   => $datetime
        ];
        $userId = $usersModel->insert($data);
        if ($userId == 0) {
            $dbBase->rollBack();
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        $userDataModel = new UserData();
        $ok = $userDataModel->initTableData($userId, $mobilephone, $realname, $realname, $email, $avatar, $signature);
        if ($ok) {
            $dbBase->commit();
            return true;
        } else {
            $dbBase->rollBack();
            return false;
        }
    }

    /**
     * 编辑用户。
     *
     * @param  int     $userId       用户ID。
     * @param  string  $username     用户名。
     * @param  string  $password     密码。
     * @param  string  $mobilephone  手机号。
     * @param  string  $email        邮箱地址。
     * @param  string  $realname     真实姓名。
     * @param  string  $avatar       头像。
     * @param  string  $signature    签名。
     * @return bool
     */
    public static function editUser($userId, $username, $password = '', $mobilephone = '', $email = '', $realname = '', $avatar = '', $signature = '')
    {
        self::checkUsername($username);
        (strlen($password) > 0) && self::checkPassword($password);
        (strlen($realname) > 0) && self::checkRealname($realname);
        (strlen($signature) > 0) && self::checkSignature($signature);
        (strlen($avatar) > 0) && self::checkAvatar($avatar);

        $dbBase     = new DbBase();
        $usersModel = new User();

        $userinfo = $usersModel->fetchOne([], ['username' => $username]);
        if ($userinfo && $userinfo['user_id'] != $userId) {
            YCore::exception(STATUS_ERROR, '该用户名已经存在请更换一个');
        }

        $userinfo   = $usersModel->fetchOne([], ['user_id' => $userId]);
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '用户不存在或已经删除');
        }

        if (strlen($mobilephone) > 0) {
            self::checkMobilephone($mobilephone);
            if ($userinfo['mobilephone'] != $mobilephone) {
                self::isExistMobilephone($mobilephone);
            }
        }
        if (strlen($email) > 0) {
            self::checkEmail($email);
            if ($userinfo['email'] != $email) {
                self::isExistEmail($email);
            }
        }
        $data = [
            'username'      => $username,
            'mobilephone'   => $mobilephone,
            'email'         => $email,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        if (strlen($password) > 0) {
            $salt             = YCore::create_randomstr(6);
            $password         = self::encryptPassword($password, $salt);
            $data['salt']     = $salt;
            $data['password'] = $password;
        }
        $ok = $usersModel->update($data, ['user_id' => $userId]);
        if (!$ok) {
            $dbBase->rollBack();
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        $userDataModel = new UserData();
        $ok = $userDataModel->editInfo($userId, $mobilephone, $realname, $realname, $email, $avatar, $signature);
        if ($ok) {
            $dbBase->commit();
            return true;
        } else {
            $dbBase->rollBack();
            return false;
        }
    }

    /**
     * 解禁用户。
     *
     * @param  int  $userId   用户ID。
     * @param  int  $adminId  管理员ID。
     * @return bool
     */
    public static function unforbid($userId, $adminId)
    {
        $usersBlacklistModel = new UserBlacklist();
        return $usersBlacklistModel->unforbiddenUser($userId, $adminId);
    }

    /**
     * 绑定第三方用户。
     *
     * @param  int     $userId     用户ID。
     * @param  string  $openid     第三方用户标识。
     * @param  string  $thirdType  第三方类型：qq、weibo、weixin。
     * @return bool
     */
    public static function thirdUserBind($userId, $openid, $thirdType)
    {
        if (strlen($openid) === 0) {
            YCore::exception(STATUS_ERROR, 'openid error');
        }
        $where = [
            'user_id'   => $userId,
            'bind_type' => $thirdType,
            'status'    => 1
        ];
        $userBindModel = new UserBind();
        $userBindInfo  = $userBindModel->fetchOne([], $where);
        if (!empty($userBindInfo)) {
            YCore::exception(STATUS_ERROR, '请不要重复绑定');
        }
        $data = [
            'user_id'      => $userId,
            'bind_type'    => $thirdType,
            'openid'       => $openid,
            'created_time' => $_SERVER['REQUEST_TIME'],
            'status'       => UserBind::STATUS_NORMAL
        ];
        $id = $userBindModel->insert($data);
        if (!$id) {
            YCore::exception(STATUS_SERVER_ERROR, '绑定失败');
        }
        return true;
    }

    /**
     * 账号封禁。
     *
     * @param  int     $adminId       管理员ID。
     * @param  int     $userId        用户ID。
     * @param  int     $banType       封禁类型。1永封禁、2临时封禁。
     * @param  string  $banStartTime  封禁开始时间。
     * @param  string  $banEndTime    封禁失效时间。
     * @param  string  $banReason     封禁原因。
     * @return bool
     */
    public static function addBlacklist($adminId, $userId, $banType, $banStartTime = '', $banEndTime = '', $banReason = '')
    {
        $timestamp     = time();
        $UserBlackList = new UserBlacklist();
        $blacklist     = $UserBlackList->fetchOne([], ['status' => UserBlacklist::STATUS_NORMAL, 'user_id' => $userId]);
        if ($blacklist) {
            if ($blacklist['ban_type'] == 1) {
                YCore::exception(STATUS_ERROR, '该用户已经被永久封禁');
            } else {
                if ($blacklist['ban_end_time'] < $timestamp) { // 如果临时封禁情况下且已经失效。则将封禁设置为无效。
                    $data = [
                        'status'        => 0,
                        'modified_by'   => $adminId,
                        'modified_time' => $timestamp
                    ];
                    $UserBlackList->update($data, ['id' => $blacklist['id']]);
                } else {
                    YCore::exception(STATUS_ERROR, '该用户已经被临时封禁还未到期');
                }
            }
        }
        $UserModel = new User();
        $userinfo  = $UserModel->fetchOne([], ['user_id' => $userId]);
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '用户不存在或已经删除');
        }
        if ($banType == 2) {
            if (strlen($banStartTime) === 0) {
                YCore::exception(STATUS_ERROR, '临时封禁时封禁开始时间必须填写');
            }
            if (strlen($banEndTime) === 0) {
                YCore::exception(STATUS_ERROR, '临时封禁时封禁失效时间必须填写');
            }
            if (!Validator::is_date($banStartTime)) {
                YCore::exception(STATUS_ERROR, '封禁开始时间格式不正确');
            }
        } else if ($banType == 1) {
            $endTime       = $timestamp + 500 * 86400 * 365; // 封禁500年代表永久封禁的意思。
            $banStartTime  = date('Y-m-d H:i:s', $timestamp);
            $banEndTime    = date('Y-m-d H:i:s', $endTime);
        }
        if (strlen($banReason) > 0 && !Validator::is_len($banReason, 1, 200, true)) {
            YCore::exception(STATUS_ERROR, '封禁原因长度最大只允许200个字符');
        }
        return $UserBlackList->forbiddenUser($adminId, $userId, $userinfo['username'], $banType, $banStartTime, $banEndTime, $banReason);
    }

    /**
     * 用户详情信息。
     *
     * @param int $userId 用户ID。
     * @return array
     */
    public static function getUserDetail($userId) {
        $baseModel = new DbBase();
        $sql = 'SELECT a.user_id,a.username,a.mobilephone,a.mobilephone_ok,a.mobilephone_time,'
             . 'a.email,a.email_ok,a.email_time,a.created_time,b.realname,b.avatar,b.signature '
             . 'FROM ms_user AS a LEFT JOIN ms_user_data AS b ON(a.user_id=b.user_id) '
             . 'WHERE a.user_id = :user_id';
        $params = [
            ':user_id' => $userId
        ];
        $userinfo = $baseModel->rawQuery($sql, $params)->rawFetchOne();
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '用户不存在或已经删除');
        }
        return $userinfo;
    }

    /**
     * 发送找回密码验证码。
     *
     * @param  int     $findType   找回密码类型：1手机号找回、2邮箱找回。
     * @param  string  $toAccount  接收验证码的手机或邮箱账号。
     * @return bool
     */
    public static function sendFindPwdCode($findType, $toAccount)
    {
        // [1] 格式验证。
        switch ($findType) {
            case 1:
                self::checkMobilephone($toAccount);
                break;
            case 2:
                self::checkEmail($toAccount);
                break;
            default :
                YCore::exception(STATUS_SERVER_ERROR, '服务器异常');
                break;
        }
        // [2] 每天每账号的不同类型找回方式只能3次。
        $date              = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        $dayStartTimestamp = strtotime("{$date} 00:00:00");
        $defaultDb         = new DbBase();
        $sql = 'SELECT COUNT(1) AS count FROM ms_find_pwd WHERE find_type = :find_type '
             . 'AND to_account = :to_account AND created_time > :created_time';
        $params = [
            ':find_type'    => $findType,
            ':to_account'   => $toAccount,
            ':created_time' => $dayStartTimestamp
        ];
        $result = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        if (!empty($result) && $result['count'] >= 3) {
            YCore::exception(STATUS_ERROR, '已经超过3次请明天再试');
        }
        switch ($findType) {
            case 1:
                $where = [
                    'mobilephone' => $toAccount
                ];
                break;
            case 2:
                $where = [
                    'email' => $toAccount
                ];
                break;
        }
        $UserModel = new User();
        $userinfo  = $UserModel->fetchOne([], $where);
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '账号不存在');
        }
        $code = YCore::create_randomstr(6);
        // [3] 发送验证码。
        // ......
        switch ($findType) {
            case 1:
                SmsService::sendSmsCode(SmsService::SMS_TYPE_FINDPWD, $toAccount);
                break;
            case 2:
                // 发送邮件。
                break;
        }
        // [4] 记录发送的验证码。
        $data = [
            'user_id'      => $userinfo['user_id'],
            'find_type'    => $findType,
            'to_account'   => $toAccount,
            'code'         => $code,
            'ip'           => YCore::ip(),
            'created_time' => $_SERVER['REQUEST_TIME']
        ];
        $FindPwdModel = new FindPwd();
        $id = $FindPwdModel->insert($data);
        if ($id == 0) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        return true;
    }

    /**
     * 找回密码。
     *
     * @param  int     $findType     找回密码类型：1手机号码找回、2邮箱找回。
     * @param  string  $toAccount    手机或邮箱账号。
     * @param  string  $code         验证码。
     * @param  string  $newPwd       新密码。
     * @return array
     */
    public static function findPwd($findType, $toAccount, $code, $newPwd) {
        // [1] 格式验证。
        switch ($findType) {
            case 1:
                self::checkMobilephone($toAccount);
                break;
            case 2:
                self::checkEmail($toAccount);
                break;
            default :
                YCore::exception(STATUS_SERVER_ERROR, '服务器异常');
                break;
        }
        self::checkCaptcha($code);
        self::checkPassword($password);
        $defaultDb = new DbBase();
        $sql = 'SELECT * FROM ms_find_pwd WHERE find_type = :find_type ' 
             . 'AND to_account = :to_account AND check_times < :check_times AND is_ok != :is_ok';
        $params = [
            ':find_type'   => $findType,
            ':to_account'  => $toAccount,
            ':check_times' => 3,
            ':is_ok'       => 1
        ];
        $result = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        if (empty($result)) {
            YCore::exception(STATUS_ERROR, '找回密码操作已过期');
        }
        $where = [
            'id'          => $result['id'],
            'check_times' => $result['check_times']
        ];
        $FindPwdModel = new FindPwd();
        if ($result['code'] == $code) {
            $data = [
                'check_times'   => $result['check_times'] + 1,
                'is_ok'         => 1,
                'modified_time' => date('Y-m-d H:i:s', time())
            ];
            $ok = $FindPwdModel->update($data, $where);
            if (!$ok) {
                YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请重试');
            }
            $salt = YCore::create_randomstr(6);
            $password = self::encryptPassword($newPwd, $salt);
            $data = [
                'password' => $password,
                'salt'     => $salt
            ];
            $where = [
                'user_id' => $result['user_id']
            ];
            $UserModel = new User();
            $ok = $UserModel->update($data, $where);
            if (!$ok) {
                YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
            }
            return true;
        } else {
            $data = [
                'check_times'   => $result['check_times'] + 1,
                'is_ok'         => 2,
                'modified_time' => date('Y-m-d H:i:s', time())
            ];
            $ok = $FindPwdModel->update($data, $where);
            if (!$ok) {
                YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请重试');
            } else {
                YCore::exception(STATUS_ERROR, '验证码不正确');
            }
        }
    }

    /**
     * 修改密码。
     *
     * @param  int     $userId   用户ID。
     * @param  string  $oldPwd   旧密码。
     * @param  string  $newPwd   新密码。
     * @return bool
     */
    public static function editPwd($userId, $oldPwd, $newPwd) {
        if (strlen($oldPwd) === 0) {
            YCore::exception(STATUS_ERROR, '原密码必须填写');
        }
        if (strlen($newPwd) === 0) {
            YCore::exception(STATUS_ERROR, '新密码必须填写');
        }
        if ($oldPwd == $newPwd) {
            YCore::exception(STATUS_ERROR, '新密码不能与原密码相同');
        }
        if (!Validator::is_alpha_dash($newPwd)) {
            YCore::exception(STATUS_ERROR, '新密码格式不正确');
        }
        if (!Validator::is_len($newPwd, 6, 20)) {
            YCore::exception(STATUS_ERROR, '新密码长度必须6~20位之间');
        }
        $UserModel = new User();
        $userinfo  = $UserModel->fetchOne([], [
            'user_id' => $userId
        ]);
        $encryptPassword = self::encryptPassword($oldPwd, $userinfo['salt']);
        if ($encryptPassword != $userinfo['password']) {
            YCore::exception(STATUS_ERROR, '原密码不正确');
        }
        $salt     = YCore::create_randomstr(6);
        $password = self::encryptPassword($newPwd, $salt);
        $updata   = [
            'salt'          => $salt,
            'password'      => $password,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $UserModel->update($updata, ['user_id' => $userId]);
        if (!$ok) {
            YCore::exception(STATUS_ERROR, '密码修改失败');
        }
        return true;
    }

    /**
     * 检查用户权限。
     * -- 1、在每次用户访问程序的时候调用。
     *
     * @param  string  $token 用户会话 TOKEN。
     * @return array
     */
    public static function checkAuth($token = '') {
        // [1] 参数判断。
        if (strlen($token) === 0) {
            YCore::exception(STATUS_ERROR, 'Token parameters must not be empty');
        }
        // [2] token解析
        $tokenParams = self::parseToken($token);
        $userId      = $tokenParams['user_id'];
        $loginTime   = $tokenParams['login_time'];
        $loginModel  = $tokenParams['mode'];
        $accessTime  = $_SERVER['REQUEST_TIME'];
        // [3] 用户存在与否判断
        $UserModel = new User();
        $userinfo  = $UserModel->fetchOne([], ['user_id' => $userId]);
        if (empty($userinfo)) {
            YCore::exception(STATUS_ERROR, '系统异常');
        }
        if ($tokenParams['password'] != $userinfo['password']) {
            YCore::exception(STATUS_ERROR, '您的密码被修改,请重新登录');
        }
        // [4] 黑名单判断
        $UserBlackListModel = new UserBlacklist();
        $result = $UserBlackListModel->isForbidden($userId);
        if ($result['status'] == 1) {
            YCore::exception(STATUS_ERROR, $result['message']);
        }
        // [5] token是否赶出了超时时限
        $cache = YCore::getCache();
        $isUniqueLogin = YCore::config('is_unique_login');
        if ($isUniqueLogin == 1) { // 排它性登录。
            $cacheKeyToken = "user_token_key_{$userId}";
            $cacheKeyTime  = "user_access_time_key_{$userId}";
            $cacheToken    = $cache->get($cacheKeyToken);
            if ($cacheToken === false) {
                YCore::exception(1000502, '系统繁忙,稍候重试');
            }
            if ($cacheToken === null) {
                YCore::exception(1000501, '登录超时,请重新登录');
            }
            if ($token != $cacheToken) {
                YCore::exception(1000503, '您的账号在其它地方登录');
            }
        } else if ($loginModel == 2) { // 非排它性登录。
            $cacheKeyToken = "user_token_key_{$loginTime}_{$userId}";
            $cacheKeyTime  = "user_access_time_key_{$loginTime}_{$userId}";
            $cacheToken    = $cache->get($cacheKeyToken);
            if ($cacheToken === false) {
                YCore::exception(1000502, '系统繁忙,请稍候重试');
            }
            if ($cacheToken === null) {
                YCore::exception(1000501, '登录超时,请重新登录');
            }
        } else {
            YCore::exception(STATUS_SERVER_ERROR, '非法操作');
        }
        self::setAuthTokenLastAccessTime($userId, $token, $accessTime, $loginModel, $loginTime);
        return [
            'user_id'     => $userId,
            'username'    => $userinfo['username'],
            'mobilephone' => $userinfo['mobilephone']
        ];
    }

    /**
     * 获取会话 token 中的用户ID。
     *
     * -- 如果 token 存在且有效就解析得到用户ID。否则返回0。
     *
     * @param  string  $token 用户会话 token。
     * @return int
     */
    public static function getTokenUserId($token) {
        try {
            $result    = self::parseToken($token);
            $userid    = intval($result['user_id']);
            $userModel = new User();
            $userinfo  = $userModel->fetchOne([], ['user_id' => $userid]);
            if (empty($userinfo)) {
                return 0;
            }
        } catch (\Exception $e) {
            $userid = 0;
        }
        return $userid;
    }

    /**
     * 将用户踢下线（退出登录）。
     *
     * @param int $userId
     * @return bool
     */
    public static function kick($userId) {
        $cache = YCore::getCache();
        $isUniqueLogin = YCore::config('is_unique_login');
        if ($isUniqueLogin == 1) {
            $cacheKeyToken = "user_token_key_{$userId}";
            $cacheKeyTime  = "user_access_time_key_{$userId}";
            $cache->delete($cacheKeyToken);
            $cache->delete($cacheKeyTime);
        } else if ($isUniqueLogin == 2) { // 非排他性登录的情况下，可能出现一个账号多次登录的情况。
            $UserLoginModel  = new UserLogin();
            $pcLogoutTime    = YCore::config('pc_logout_time') * 60 + 60; // 多加60，是避免边界值误差。
            $startTime       = date('Y-m-d H:i:s', (time() - $pcLogoutTime));
            $endTime         = date('Y-m-d H:i:s', time());
            $loginRecordList = $UserLoginModel->getUserLoginRecord($userId, $startTime, $endTime);
            foreach ($loginRecordList as $record) {
                $loginTime     = strtotime($record['login_time']);
                $cacheKeyToken = "user_token_key_{$loginTime}_{$userId}";
                $cacheKeyTime  = "user_access_time_key_{$loginTime}_{$userId}";
                $cache->delete($cacheKeyToken);
                $cache->delete($cacheKeyTime);
            }
        }
        return true;
    }

    /**
     * 设置 auth_token 最后的访问时间。
     *
     * @param  int     $userId      用户ID。
     * @param  string  $authToken   auth_token。
     * @param  int     $accessTime  最后访问时间戳。
     * @param  int     $loginModel  登录模式。1:web模式、2:接口模式。
     * @param  int     $loginTime   登录时间。
     * @return void
     */
    private static function setAuthTokenLastAccessTime($userId, $authToken, $accessTime, $loginModel, $loginTime)
    {
        $cache = YCore::getCache();
        // [1] 不同的登录模式。缓存的时间各不相同。
        if ($loginModel == 1) {
            $cache_time = YCore::config('pc_logout_time') * 60;
        } else if ($loginModel == 2) {
            $cache_time = YCore::config('app_logout_time') * 86400;
        }
        // [2] 排它性登录实现的原理各有差异。
        $isUniqueLogin = YCore::config('is_unique_login'); // 是否排它性登录。
        // 排它性是指同一账号只允许同一时间只能允许一个人登录在线。
        if ($isUniqueLogin == 1) {
            $cacheKeyToken = "user_token_key_{$userId}";         // 用户保存auth_token的缓存键。
            $cacheKeyTime  = "user_access_time_key_{$userId}";   // 用户保存最后访问时间的缓存键。
            $cache->set($cacheKeyToken, $authToken, $cache_time);
            $cache->set($cacheKeyTime, $accessTime, $cache_time);
        } else {
            $cacheKeyToken = "user_token_key_{$loginTime}_{$userId}";       // 用户保存auth_token的缓存键。
            $cacheKeyTime  = "user_access_time_key_{$loginTime}_{$userId}"; // 用户保存最后访问时间的缓存键。
            $cache->set($cacheKeyToken, $authToken, $cache_time);
            $cache->set($cacheKeyTime, $accessTime, $cache_time);
        }
    }

    /**
     * 加密密码。
     *
     * @param  string  $password  密码明文。
     * @param  string  $salt      密码加密盐。
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
     * @param  int      $userId      用户ID。
     * @param  string   $password    用户表password字段。
     * @param  int      $loginTime   登录时间(时间戳)。
     * @param  boolean  $loginModel  登录模式。1web模式、2接口模式。
     * @return string
     */
    public static function createToken($userId, $password, $loginTime, $loginModel = 1)
    {
        $str = "{$userId}\t{$password}\t{$loginTime}\t{$loginModel}";
        return YCore::sys_auth($str, 'ENCODE', '', 0);
    }

    /**
     * 解析Token。
     *
     * @param  string  $token token会话。
     * @return array
     */
    public static function parseToken($token)
    {
        $data = YCore::sys_auth($token, 'DECODE');
        $data = explode("\t", $data);
        if (count($data) != 4) {
            YCore::exception(STATUS_ERROR, '登录超时,请重新登录');
        }
        $result = [
            'user_id'    => $data[0], // 用户ID。
            'password'   => $data[1], // 加密的ID。
            'login_time' => $data[2], // 登录时间。
            'mode'       => $data[3]
        ]; // token模式。1接口模式、0非接口模式。
        return $result;
    }

    /**
     * 检查手机号格式。
     * @param  string  $mobilephone 手机号码。
     * @return void
     */
    public static function checkMobilephone($mobilephone)
    {
        $data  = ['mobilephone' => $mobilephone];
        $rules = [
            'mobilephone' => '手机号码|require|alpha_dash|len:6:20:0'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查用户格式。
     * @param  string  $username 用户名。
     * @return void
     */
    public static function checkUsername($username)
    {
        $data  = ['username' => $username];
        $rules = [
            'username' => '用户名|require|alpha_dash|len:3:20:0'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查密码格式。
     * @param  string $password 密码。
     * @return void
     */
    public static function checkPassword($password)
    {
        $data  = ['password' => $password];
        $rules = [
            'password' => '密码|require|alpha_dash|len:6:20:0'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查验证码格式。
     * @param  string $code 验证码。
     * @return void
     */
    public static function checkCaptcha($code) {
        $data  = ['code' => $code];
        $rules = [
            'code' => '验证码|require|alpha_number|len:4:8:0'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 查检真实姓名格式。
     * @param  string $realname 真实姓名。
     * @return void
     */
    public static function checkRealname($realname) {
        $data  = ['realname' => $realname];
        $rules = [
            'realname' => '真实姓名|len:2:20:1'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查签名格式。
     * @param  string $signature 签名。
     * @return void
     */
    public static function checkSignature($signature) {
        $data  = ['signature' => $signature];
        $rules = [
            'signature' => '签名|len:2:20:1'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查头像格式。
     * @param  string $avatar 头像地址。
     * @return void
     */
    public static function checkAvatar($avatar) {
        $data  = ['avatar' => $avatar];
        $rules = [
            'avatar' => '头像|url|len:1:150:1'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 检查邮箱枨格式。
     * @param  string $email 邮箱地址。
     * @return void
     */
    public static function checkEmail($email) {
        $data  = ['email' => $email];
        $rules = [
            'email' => '邮箱|len:1:50:1'
        ];
        Validator::valido($data, $rules);
    }

    /**
     * 用户名是否已经存在。
     * @param  string  $username 用户名。
     * @param  string  $errMsg   验证不通过时的提示信息。
     * @return void
     */
    public static function isExistUsername($username, $errMsg = '') {
        $UserModel = new User();
        $where = [
            'username' => $username
        ];
        $result = $UserModel->fetchOne([], $where);
        if (!empty($result)) {
            $errMsg = (strlen($errMsg) > 0) ? $errMsg : '用户名已经被人注册';
            YCore::exception(STATUS_ERROR, $errMsg);
        }
    }

    /**
     * 邮箱是否已经存在。
     * @param  string  $email   邮箱。
     * @param  string  $errMsg  验证不通过时的提示信息。
     * @return void
     */
    public static function isExistEmail($email, $errMsg = '') {
        $UserModel = new User();
        $where = [
            'email' => $email
        ];
        $result = $UserModel->fetchOne([], $where);
        if (!empty($result)) {
            $errMsg = (strlen($errMsg) > 0) ? $errMsg : '邮箱是否已经存在';
            YCore::exception(STATUS_ERROR, $errMsg);
        }
    }

    /**
     * 手机号码是否已经存在。
     * @param  string  $mobilephone 手机号码。
     * @param  string  $errMsg      验证不通过时的提示信息。
     * @return void
     */
    public static function isExistMobilephone($mobilephone, $errMsg = '') {
        $UserModel = new User();
        $where = [
            'mobilephone' => $mobilephone
        ];
        $result = $UserModel->fetchOne([], $where);
        if (!empty($result)) {
            $errMsg = (strlen($errMsg) > 0) ? $errMsg : '手机号码已经被人注册';
            YCore::exception(STATUS_ERROR, $errMsg);
        }
    }
}