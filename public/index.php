<?php
/**
 * 前台入口文件。
 * -- 1、入口文件会判断当前域名去加载配置文件。
 * @author fingerQin
 * @date 2016-09-07
 */

// 微秒。
define('MICROTIME', microtime());

define("APP_PATH", realpath(dirname(dirname(__FILE__))));

require(APP_PATH . '/config/define.php');

$app = new \Yaf_Application(APP_PATH . "/config/config.ini", 'conf');
$app->bootstrap()->run();
