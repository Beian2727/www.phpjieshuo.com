<?php
/**
 * 系统常量。
 * @author fingerQin
 * @date 2017-07-08
 */

define('ADMIN_STATUS_LOGIN_TIMEOUT', 901);  // 管理员:登录超时。
define('ADMIN_STATUS_NOT_LOGIN', 902);      // 管理员:未登录。
define('ADMIN_STATUS_OTHER_LOGIN', 903);    // 管理员:其他人登录。
define('ADMIN_STATUS_FORBIDDEN', 904);      // 管理员:其他人登录。

define('STATUS_SUCCESS', 200);              // 请求成功。
define('STATUS_FORBIDDEN', 403);            // 没权限。
define('STATUS_NOT_FOUND', 404);            // 请求找不到。
define('STATUS_SERVER_ERROR', 500);         // 服务器错误。
define('STATUS_ERROR', 502);                // 业务错误专用码。

define('STATUS_LOGIN_TIMEOUT', 601);        // 用户登录超时。
define('STATUS_NOT_LOGIN', 602);            // 用户未登录。
define('STATUS_OTHER_LOGIN', 603);          // 其他人登录。
define('STATUS_ALREADY_REGISTER', 604);     // 账号已注册。
define('STATUS_UNREGISTERD', 605);          // 账号已经注册。