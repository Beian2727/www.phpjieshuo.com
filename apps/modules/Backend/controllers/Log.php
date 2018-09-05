<?php
/**
 * 日志管理。
 * @author fingerQin
 * @date 2016-01-14
 */

use common\YCore;
use services\LogService;
use finger\Paginator;

class LogController extends \common\controllers\Admin
{
    /**
     * 日志查看 。
     */
    public function indexAction()
    {
        $options = [
            'starttime' => $this->getString('starttime', ''),
            'endtime'   => $this->getString('endtime', ''),
            'errcode'   => $this->getString('errcode', ''),
            'content'   => $this->getString('content', ''),
            'page'      => $this->getInt(YCore::appconfig('pager'), 1),
            'count'     => $this->getInt('count', 50)
        ];
        $result    = LogService::getLogList($options);
        $paginator = new Paginator($result['total'], 50);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('search', $options);
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $result['list']);
        $this->assign('search', $options);
    }

    /**
     * 日志详情。
     */
    public function detailAction()
    {
        $logId  = $this->getInt('log_id');
        $detail = LogService::getLogDetail($logId);
        $this->assign('detail', $detail);
    }
}