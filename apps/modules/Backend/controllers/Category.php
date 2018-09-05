<?php
/**
 * 文章分类管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use services\CategoryService;
use common\YCore;

class CategoryController extends \common\controllers\Admin
{
    /**
     * 分类列表。
     */
    public function indexAction()
    {
        $catType     = $this->getInt('cat_type', 1);
        $list        = CategoryService::getCategoryList(0, $catType);
        $catTypeList = YCore::dict('category_type_list');
        $this->assign('list', $list);
        $this->assign('cat_type', $catType);
        $this->assign('cat_type_list', $catTypeList);
    }

    /**
     * 添加分类。
     */
    public function addAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $catType  = $this->getInt('cat_type', -1);
            $catName  = $this->getString('cat_name');
            $parentid = $this->getInt('parentid');
            $isOutUrl = $this->getInt('is_out_url');
            $outUrl   = $this->getString('out_url');
            $display  = $this->getInt('display');
            CategoryService::addCategory($this->admin_id, $catType, $catName, $parentid, $isOutUrl, $outUrl, $display);
            $this->json(true, '操作成功');
        }
        $parentid      = $this->getInt('parentid', 0);
        $parentCatInfo = [];
        if ($parentid > 0) {
            $parentCatInfo = CategoryService::getCategoryDetail($parentid);
        }
        $catTypeList = YCore::dict('category_type_list');
        $list        = CategoryService::getCategoryList(0);
        $this->assign('parentid', $parentid);
        $this->assign('list', $list);
        $this->assign('parent_cat_info', $parentCatInfo);
        $this->assign('cat_type_list', $catTypeList);
    }

    /**
     * 编辑分类。
     */
    public function editAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $catId    = $this->getInt('cat_id');
            $catName  = $this->getString('cat_name');
            $isOutUrl = $this->getInt('is_out_url');
            $outUrl   = $this->getString('out_url');
            $display  = $this->getInt('display');
            CategoryService::editCategory($this->admin_id, $catId, $catName, $isOutUrl, $outUrl, $display);
            $this->json(true, '操作成功');
        }
        $parentid    = $this->getInt('parentid', 0);
        $catId       = $this->getInt('cat_id');
        $catTypeList = YCore::dict('category_type_list');
        $detail      = CategoryService::getCategoryDetail($catId);
        $list        = CategoryService::getCategoryList(0);
        $this->assign('parentid', $parentid);
        $this->assign('detail', $detail);
        $this->assign('list', $list);
        $this->assign('cat_type_list', $catTypeList);
    }

    /**
     * 删除分类。
     */
    public function deleteAction()
    {
        $catId = $this->getInt('cat_id');
        CategoryService::deleteCategory($this->admin_id, $catId);
        $this->json(true, '删除成功');
    }

    /**
     * 分类排序。
     */
    public function sortAction()
    {
        if ($this->_request->isPost()) {
            $listorders = $this->getArray('listorders');
            CategoryService::sortCategory($listorders);
            $this->json(true, '排序成功');
        }
    }

    /**
     * 根据分类 ID 获取子分类列表并以 JSON 格式返回。
     */
    public function getListJsonAction()
    {
        if ($this->_request->isPost()) {
            $catId   = $this->getInt('cat_id', 0);
            $catType = $this->getInt('cat_type');
            $catList = CategoryService::getCategoryList($catId, $catType, true, true);
            $this->json(true, 'success', $catList);
        }
    }
}