<?php
/**
 * 日志记录。
 * @author fingerQin
 * @date 2018-02-11
 */

namespace common;

use common\YDir;
use models\ErrorLog;

class YLog
{
    /**
     * 写日志。
     *
     * @param  string  $logContent    日志内容。
     * @param  string  $logDir        日志目录。如：bank
     * @param  string  $logFileName   日志文件名称。如：bind。生成文件的时候会在 bind 后面接上日期。如:bind-20171121.log
     * @param  string  $logTime       日志产生时间。格式：2017-05-27 12:00:00
     * @param  int     $errCode       错误码。写入数据库的时候可以根据这些错误码快速检索。默认 0 代表没错或未写入。
     * @param  bool    $isWriteDb     是否将日志写入数据库。默认不写入。
     * @param  bool    $isDaySplit    是否用天来分隔日志。默认 true 。
     * @param  bool    $isOnlyContent 日志文件当中是否仅保存传入的内容。默认 false 。
     * 
     * @return void
     */
    public static function save($logContent, $logDir = '', $logFileName = '', $logTime = '', $errCode = 0, $isWriteDb = false, $isDaySplit = true, $isOnlyContent = false)
    {
        $logContent  = is_array($logContent) ? print_r($logContent, true) : $logContent;
        $time        = time();
        $logTime     = strlen($logTime) > 0 ? $logTime : date('Y-m-d H:i:s', $time);
        $logfile     = date('Ymd', $time);
        $logFileName = strlen($logFileName) > 0 ? $logFileName : 'log';
        $logDir      = strlen($logDir) > 0 ? $logDir : 'system';
        $logDir      = trim($logDir, '/');
        $logPath     = APP_PATH . "/logs/" . $logDir;
        YDir::dir_create($logPath);
        if ($isDaySplit) {
            $logPath .= "/{$logFileName}-{$logfile}.log";
        } else {
            $logPath .= "/{$logFileName}.log";
        }
        if (!$isOnlyContent) {
            $logContent = "ErrorTime:{$logTime}\nErrorCode:{$errCode}\nErrorLog:{$logContent}\n\n";
        }
        file_put_contents($logPath, $logContent, FILE_APPEND | LOCK_EX);
        if ($isWriteDb) {
            $model = new \models\ErrorLog();
            $model->addLog($logContent, $logfile, $errCode);
        }
    }

    /**
     * 快速 Debug 数据写入。
     *
     * @param  string  $logContent  日志内容。
     * @return void
     */
    public static function debug($logContent)
    {
        self::save($logContent, 'debug');
    }

    /**
     * 访问日志。
     *
     * @param  array   $postParams  POST 请求参数。GET 参数直接用 URL 来记录即可。
     * @param  string  $ip          访问的 IP。
     * @param  string  $url         
     * @return void
     */
    public static function accessLog($postParams, $ip, $url)
    {
        $log = [
            'post' => $postParams,
            'ip'   => $ip,
            'url'  => $url
        ];
        self::save($log, 'access', 'log');
    }
}