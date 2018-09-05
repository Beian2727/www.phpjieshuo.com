<?php
/**
 * 技术文档。
 * @author fingerQin
 * @date 2018-01-11
 */

use finger\Paginator;
use common\YCore;
use services\CategoryService;
use services\NewsService;

class DocController extends \common\controllers\User 
{
    /**
     * 资料搜索（查看）。
     */
    public function searchAction()
    {
        $keywords = $this->getString('keywords', '');
        $page     = $this->getInt(YCore::appconfig('pager'), 1);
        $result   = NewsService::getNewsList($keywords, '', $page, 20);
        $this->assign('result', $result);
        $this->assign('keywords', $keywords);
    }

    /**
     * 技术分类。
     */
    public function categoryAction()
    {
        $catList = CategoryService::getByParentToCategory(0, CategoryService::CAT_DOC);
        $this->assign('catList', $catList);
    }

    /**
     * 文档查看。
     */
    public function viewAction()
    {
        $catId      = $this->getInt('cat_id', 0);
        $newsId     = $this->getInt('news_id', 0);
        $catList    = CategoryService::getByParentToCategory(0, CategoryService::CAT_DOC);
        $catalogue  = NewsService::getDocCatalogue($catId);
        $treeNewsId = $this->getCatTreeFirstNewsId($catalogue);
        $newsId     = $newsId ?: $treeNewsId;
        $detail     = NewsService::getNewsDetailOrDefault($newsId, true);
        $this->assign('catalogue', $catalogue);
        $this->assign('newsId', $newsId);
        $this->assign('catList', $catList);
        $this->assign('catId', $catId);
        $this->assign('detail', $detail);
    }

    /**
     * 获取文档目录树第一篇文章。
     * 
     * @param  array  $catalogue  分类目录树。
     * @return int 0-没有文章、大于0-文章ID。
     */
    private function getCatTreeFirstNewsId($catalogue)
    {
        $firstNewsId = 0;
        foreach ($catalogue as $item) {
            if (!empty($item['news'])) {
                return $item['news'][0]['news_id'];
            }
        }
        return $firstNewsId;
    }
}