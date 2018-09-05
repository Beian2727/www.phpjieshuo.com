<?php
/**
 * 菜单管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use common\YCore;
use services\FileService;
use finger\Paginator;

class FileController extends \common\controllers\Admin
{
    /**
     * 文件列表。
     */
    public function indexAction()
    {
        $userType  = $this->getInt('user_type', -1);
        $userName  = $this->getString('user_name', '');
        $fileMd5   = $this->getString('file_md5', '');
        $fileType  = $this->getInt('file_type', -1);
        $startTime = $this->getString('start_time', '');
        $endTime   = $this->getString('end_time', '');
        $page      = $this->getInt(YCore::appconfig('pager'), 1);
        $list      = FileService::getFileList($userType, $userName, $fileMd5, $fileType, $startTime, $endTime, $page, 20);
        $paginator = new Paginator($list['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $list['list']);
        $this->assign('user_type', $userType);
        $this->assign('user_name', $userName);
        $this->assign('file_md5', $fileMd5);
        $this->assign('file_type', $fileType);
        $this->assign('start_time', $startTime);
        $this->assign('end_time', $endTime);
    }

    /**
     * 删除文件。
     */
    public function deleteAction()
    {
        $fileId = $this->getInt('file_id');
        FileService::deleteFile($fileId, $this->admin_id);
        $this->json(true, '删除成功');
    }
}