<?php
/**
 * 公共的引导程序。
 * -- 1、以_init开头的方法, 都会被Yaf调用。非_init方法不会被调用。
 * -- 2、所有方法都接受一个参数:\\Yaf\Dispatcher $dispatcher调用的次序, 和申明的次序相同。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace common;

use finger\DbBase;

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,这些方法,
 * 都接受一个参数:\\Yaf\Dispatcher $dispatcher调用的次序, 和申明的次序相同。
 */
class Bootstrap extends \Yaf_Bootstrap_Abstract
{
    /**
     * 注册配置到全局环境。
     * -- 1、率先执行，以便后续的程序都能读取到配置文件。
     */
    public function _initConfig()
    {
        $config = \Yaf_Application::app()->getConfig();
        \Yaf_Registry::set("config", $config);
        date_default_timezone_set($config->get('timezone'));
    }

    /**
     * 错误相关操作初始化。
     * -- 1、开/关PHP错误。
     * -- 2、接管PHP错误。
     */
    public function _initError()
    {
        $config        = \Yaf_Registry::get("config");
        $displayErrors = $config->error->display->errors;
        ini_set('display_errors', $displayErrors);
        set_error_handler(['\common\YCore', 'errorHandler']);
        register_shutdown_function(['\common\YCore', 'registerShutdownFunction']);
    }

    /**
     * 设置默认模块、控制器、动作。
     *
     * @param \Yaf\Dispatcher $dispatcher
     */
    public function _initDefaultName(\Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->setDefaultModule("Index")
                   ->setDefaultController("Index")
                   ->setDefaultAction("index");
    }

    /**
     * 初始化session到reids中。
     * --------------------------------------
     * 1、实现SessionHandlerInterface接口，将session保存到reids中。
     * 2、重新开启session，让默认的session切换到自已的session接口。
     * 3、第二步中直接影响\Yaf\Session的工作方式。
     * 4、或者直接关闭SESSION的使用。
     * --------------------------------------
     */
    public function _initSession(\Yaf_Dispatcher $dispatcher)
    {
        // 采用 COOKIE 保存会话 TOKEN 模式。不再需要 SESSION。
        // $mysql   = new DbBase();
        // $db_link = $mysql->getDbClient ();
        // $sess    = new \finger\session\mysql\SessionHandler($db_link, null, 'sess_');
        // session_set_save_handler($sess);
        // $session = \Yaf_Session::getInstance();
        // \Yaf_Registry::set('session', $session);
    }

    /**
     * 注册插件。
     * --1、Yaf框架会根据特有的类名后缀(DbBase、Controller、Plugin)进行自动加载。为避免这种情况请不要以这样的名称结尾。
     * --2、 插件可能会用到缓存、数据库、配置等。所以，放到最后执行。
     *
     * @param \\Yaf\Dispatcher $dispatcher
     */
    public function _initPlugin(\Yaf_Dispatcher $dispatcher)
    {
        // $PageCachePlugin = new \common\plugins\PageCache();
        // $dispatcher->registerPlugin($PageCachePlugin);
    }
}
