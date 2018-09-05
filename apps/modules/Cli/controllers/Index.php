<?php
/**
 * 默认controller。
 * @author fingerQin
 * @date 2015-11-13
 */

use finger\Thread;
use services\GuessService;
use models\Ad;
use models\TestDB\AdminMenu;
use common\YCore;

class IndexController extends \common\controllers\Cli
{
    /**
     * 首页。
     */
    public function indexAction()
    {
        echo 'ok';
    }

    /**
     * 多进程测试。
     */
    public function threadsAction()
    {
        $objThread   = \threads\TestThread::getInstance(20);
        $objThread->setChildOverNewCreate(false);
        $objThread->start();
    }
}