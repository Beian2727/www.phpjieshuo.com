<?php
/**
 * 文章管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use finger\Paginator;
use common\YCore;
use common\YUrl;
use services\NewsService;
use services\CategoryService;
use services\UploadService;

class NewsController extends \common\controllers\Admin
{
    /**
     * 文章列表。
     */
    public function indexAction()
    {
        $title     = $this->getString('title', '');
        $adminName = $this->getString('admin_name', '');
        $starttime = $this->getString('starttime', '');
        $endtime   = $this->getString('endtime', '');
        $page      = $this->getInt(YCore::appconfig('pager'), 1);
        $list      = NewsService::getAdminNewsList($title, $adminName, $starttime, $endtime, $page, 20);
        $paginator = new Paginator($list['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $list['list']);
        $this->assign('admin_name', $adminName);
        $this->assign('title', $title);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
    }

    /**
     * 添加文章。
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $title    = $this->getString('title');
            $catCode  = $this->getString('cat_code');
            $intro    = $this->getString('intro');
            $keywords = $this->getString('keywords');
            $source   = $this->getString('source');
            $imageUrl = $this->getString('image_url');
            $content  = $this->getString('content');
            $display  = $this->getInt('display');
            NewsService::addNews($this->admin_id, $catCode, $title, $intro, $keywords, $source, $imageUrl, $content, $display);
            $this->json(true, '操作成功');
        }
        $newsCatList     = CategoryService::getCategoryList(0, CategoryService::CAT_DOC, true);
        $frontendUrl     = YUrl::getDomainName();
        $filesDomainName = YUrl::getDomainName();
        $this->assign('files_domain_name', $filesDomainName);
        $this->assign('news_cat_list', $newsCatList);
        $this->assign('frontend_url', $frontendUrl);
    }

    /**
     * 编辑文章。
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $newsId   = $this->getInt('news_id');
            $catCode  = $this->getString('cat_code');
            $title    = $this->getString('title');
            $intro    = $this->getString('intro');
            $keywords = $this->getString('keywords');
            $source   = $this->getString('source');
            $imageUrl = $this->getString('image_url');
            $content  = $this->getString('content');
            $display  = $this->getInt('display');
            NewsService::editNews($this->admin_id, $newsId, $catCode, $title, $intro, $keywords, $source, $imageUrl, $content, $display);
            $this->json(true, '操作成功');
        }
        $newsId          = $this->getInt('news_id');
        $detail          = NewsService::getNewsDetail($newsId, true);
        $newsCatList     = CategoryService::getCategoryList(0, 1);
        $frontendUrl     = YUrl::getDomainName();
        $filesDomainName = YUrl::getDomainName();
        $this->assign('files_domain_name', $filesDomainName);
        $this->assign('news_cat_list', $newsCatList);
        $this->assign('detail', $detail);
        $this->assign('frontend_url', $frontendUrl);
    }

    /**
     * 文章删除。
     */
    public function deleteAction()
    {
        $newsId = $this->getInt('news_id');
        NewsService::deleteNews($this->admin_id, $newsId);
        $this->json(true, '操作成功');
    }

    /**
     * 文章排序。
     */
    public function sortAction()
    {
        if ($this->_request->isPost()) {
            $listorders = $this->getArray('listorders');
            NewsService::sortNews($this->admin_id, $listorders);
            $this->json(true, '排序成功');
        }
    }

    /**
     * 图片文件上传。
     */
    public function uploadAction()
    {
        header("Access-Control-Allow-Origin: *");
        try {
            $uploadType = $this->getString('dir', 'image');
            if ($uploadType == 'file') {
                $result = UploadService::uploadOtherFile(1, $this->admin_id, 'files', 2, 'imgFile');
            } else { // 图片。
                $result = UploadService::uploadImage(1, $this->admin_id, 'news', 2, 'imgFile');
            }
            echo json_encode(['error' => 0, 'url' => $result['image_url']]);
        } catch (\Exception $e) {
            echo json_encode(['error' => 1, 'message' => $e->getMessage()]);
        }
        $this->end();
    }
}