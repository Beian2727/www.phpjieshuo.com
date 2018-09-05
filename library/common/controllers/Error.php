<?php
/**
 * 公用异常处理。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace common\controllers;

use common\YCore;
use common\YLog;
use common\YUrl;

class Error extends \common\controllers\Common
{
    /**
     * 也可通过$request->getException()获取到发生的异常
     */
    public function errorAction($exception)
    {
        $errcode = strval($exception->getCode());
        $trace   = $this->logWrapper($exception->__toString());

        $errMsgTpl = [
            STATUS_SUCCESS              => '请求成功',
            STATUS_FORBIDDEN            => '您没有权限访问',
            STATUS_NOT_FOUND            => '您访问的资源不存在',
            STATUS_SERVER_ERROR         => '服务器繁忙,请稍候重试',

            STATUS_LOGIN_TIMEOUT        => '登录超时,请重新登录',
            STATUS_NOT_LOGIN            => '您还未登录',
            STATUS_OTHER_LOGIN          => '您的账号在其他地方登录',
            STATUS_ALREADY_REGISTER     => '您的账号已经注册',
            STATUS_UNREGISTERD          => '您的账号还未注册',

            ADMIN_STATUS_LOGIN_TIMEOUT  => '登录超时,请重新登录',
            ADMIN_STATUS_NOT_LOGIN      => '您还未登录',
            ADMIN_STATUS_LOGIN_TIMEOUT  => '您的账号在其他地方登录'
        ];

        // 如果错误码在错误信息模板当中存在或者是特殊码 STATUS_ERROR，则错误码为原错误码。否则为服务器异常错误码STATUS_SERVER_ERROR。
        $oriErrCode = $errcode; // 原始错误码。避免后续错误码业务化调整修改它。
        $errcode    = (isset($errMsgTpl[$errcode]) || $errcode == STATUS_ERROR) ? $errcode : STATUS_SERVER_ERROR;
        $errmsg     = ($errcode == STATUS_ERROR) ? $exception->getMessage() : $errMsgTpl[$errcode];
        $datetime   = date('Y-m-d H:i:s', time());

        // 写日志。
        $isWriteDb = ($errcode == STATUS_ERROR) ? false : true; // 业务错误的日志不写入数据库。

        if ($oriErrCode == -1) {
            YLog::save($trace, 'php', 'log', $datetime, $errcode, $isWriteDb);
        } else {
            YLog::save($trace, 'errors', 'log', $datetime, $errcode, $isWriteDb);
        }
        if ($this->isApiPost($this->_request) || $this->_request->isXmlHttpRequest() || $this->_request->isCli()) {
            $data = [
                'code' => $errcode,
                'msg'  => $errmsg
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            // 记录 API 请求日志。
            $log = \finger\Log::getInstance();
            $log->write('response:' . var_export($data, true));
            $log->store();
            $this->end();
        } else {
            $this->error("{$errmsg}", '', 0);
        }
    }

    /**
     * 错误信息包装器。
     *
     * @param  string  $log_content 错误信息。
     * @return string
     */
    protected function logWrapper($log_content)
    {
        $current_url = YUrl::getUrl();
        $ip          = YCore::ip();
        return "{$log_content}\nRequest IP:{$ip}\nRequest Url:{$current_url}";
    }

    /**
     * 是否是 API 接口请求。
     *
     * @param  Yaf_Request_Abstract  $request
     * @return boolean
     */
    protected function isApiPost($request)
    {
        if (PHP_SAPI != 'cli' && strtolower($request->getModuleName()) == 'api') {
            return true;
        } else {
            return false;
        }
    }
}