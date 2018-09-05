<?php
/**
 * 命令行运行入口文件。
 * 
 * @author fingerQin
 * 
 * @date 2017-06-12
 */

define('APP_PATH', realpath(dirname(dirname(__FILE__))));

require(APP_PATH . '/apps/conf/define.php');

(new \Yaf_Application(APP_PATH . "/apps/conf/application.ini", 'conf'))->bootstrap();

if (!isset($argv[1])) {
    exit("Please enter the route to execute. Example: the php cli.php Index/Index!\n");
}

$routeArr = explode('/', $argv[1]);
if (count($routeArr) != 2) {
    exit("Please enter the route to execute. Example: the php cli.php Index/Index!\n");
}

$controllerName = $routeArr[0];
$actionName     = $routeArr[1];

$request = new \Yaf_Request_Simple('CLI', 'Cli', $controllerName, $actionName);
\Yaf_Application::app()->getDispatcher()->returnResponse(true)->dispatch($request);