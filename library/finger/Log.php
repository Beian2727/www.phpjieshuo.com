<?php
/**
 * 应用 API 日志处理。
 * 
 * @author fingerQin
 * @date 2017-08-08
 */

namespace finger;

use common\YCore;

class Log
{
    /**
     * 当前对象实例。
     *
     * @var finger\Log
     */
    private static $_instance;

    private function __construct() {}

    /**
     * 防止克隆导致单例失败。
     * 
     * @return void
     */
    private function __clone() {}

    /**
     * 获取当前对象实例。
     * 
     * @return finger\Log
     */
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)) {    
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 写日志(只是暂存,不会直接写入)。
     * 
     * @param  string  $log 日志内容。
     *
     * @return void
     */
    public function write($log)
    {
        $GLOBALS['_log'] = isset($GLOBALS['_log']) ? $GLOBALS['_log'] : '';
        $GLOBALS['_log'] = $GLOBALS['_log'] . "\n" . $log;
    }

    /**
     * 写入文件。
     * 
     * -- 将当前批次请求的日志写入文件当中。
     * 
     * @return void
     */
    public function store()
    {
        $log       = isset($GLOBALS['_log']) ? $GLOBALS['_log'] : '';
        $logTime   = date('Y-m-d H:i:s', time());
        $logfile   = date('Ymd', time());
        $logPath   = APP_PATH . '/logs/apis/' . $logfile . '.log';
        $storeLog  = "-----------------------------------------------\n";
        $storeLog .= "LogTime:{$logTime}\n{$log}\n\n";
        file_put_contents($logPath, $storeLog, FILE_APPEND);
    }
}