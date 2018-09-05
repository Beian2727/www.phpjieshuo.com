<?php
/**
 * 字典管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use finger\Paginator;
use common\YCore;
use services\DictService;

class DictController extends \common\controllers\Admin
{
    /**
     * 字典类型列表。
     */
    public function indexAction()
    {
        $keywords  = $this->getString('keywords', '');
        $page      = $this->getInt(YCore::appconfig('pager'), 1);
        $list      = DictService::getDictTypeList($keywords, $page, 10);
        $paginator = new Paginator($list['total'], 10);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('keywords', $keywords);
        $this->assign('dict_list', $list);
    }

    /**
     * 字典类型下所属的字典数据。
     */
    public function dictAction()
    {
        $dictTypeId = $this->getInt('dict_type_id');
        $keywords   = $this->getString('keywords', '');
        $page       = $this->getInt(YCore::appconfig('pager'), 1);
        $list       = DictService::getDictList($dictTypeId, $keywords, $page, 10);
        $paginator  = new Paginator($list['total'], 10);
        $pageHtml   = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('keywords', $keywords);
        $this->assign('dict_type_id', $dictTypeId);
        $this->assign('list', $list);
    }

    /**
     * 添加字典。
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $dictValue   = $this->getString('dict_value');
            $dictCode    = $this->getString('dict_code');
            $description = $this->getString('description');
            $dictTypeId  = $this->getInt('dict_type_id');
            DictService::addDict($dictTypeId, $dictCode, $dictValue, $description, 0, $this->admin_id);
            $this->json(true, '添加成功');
        }
        $dictTypeId = $this->getInt('dict_type_id');
        $this->assign('dict_type_id', $dictTypeId);
    }

    /**
     * 编辑字典。
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $dictId      = $this->getInt('dict_id');
            $dictValue   = $this->getString('dict_value');
            $dictCode    = $this->getString('dict_code');
            $description = $this->getString('description');
            $dictTypeId  = $this->getInt('dict_type_id');
            DictService::editDict($dictId, $dictCode, $dictValue, $description, 0, $this->admin_id);
            $this->json(true, '修改成功');
        }
        $dictId     = $this->getInt('dict_id');
        $dict       = DictService::getDict($dictId);
        $dictTypeId = $this->getInt('dict_type_id');
        $this->assign('dict', $dict);
        $this->assign('dict_type_id', $dictTypeId);
    }

    /**
     * 字典删除。
     */
    public function deleteAction()
    {
        $dictId = $this->getInt('dict_id');
        DictService::deleteDict($dictId, $this->admin_id);
        $this->json(true, '删除成功');
    }

    /**
     * 字典值排序。
     */
    public function sortDictAction()
    {
        if ($this->_request->isPost()) {
            $dictTypeId = $this->getInt('dict_type_id');
            $listorders = $this->getGP('listorders');
            DictService::sortDict($this->admin_id, $listorders);
            $this->json(true, '排序成功');
        }
        $this->end();
    }

    /**
     * 添加字典类型。
     */
    public function addTypeAction()
    {
        if ($this->_request->isPost()) {
            $typeName    = $this->getString('type_name');
            $typeCode    = $this->getString('type_code');
            $description = $this->getString('description');
            DictService::addDictType($this->admin_id, $typeCode, $typeName, $description);
            $this->json(true, '字典添加成功');
        }
    }

    /**
     * 编辑字典类型。
     */
    public function editTypeAction()
    {
        if ($this->_request->isPost()) {
            $dictTypeId  = $this->getInt('dict_type_id');
            $typeName    = $this->getString('type_name');
            $typeCode    = $this->getString('type_code');
            $description = $this->getString('description');
            DictService::editDictType($this->admin_id, $dictTypeId, $typeCode, $typeName, $description);
            $this->json(true, '修改成功');
        }
        $dictTypeId = $this->getInt('dict_type_id');
        $dict       = DictService::getDictType($dictTypeId);
        $this->assign('dict', $dict);
    }

    /**
     * 删除字典类型。
     */
    public function deleteTypeAction()
    {
        $dictTypeId = $this->getInt('dict_type_id');
        DictService::deleteDictType($this->admin_id, $dictTypeId);
        $this->json(true, '操作成功');
    }

    /**
     * 清除字典缓存。
     */
    public function clearCacheAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            DictService::clearDictCache();
            $this->json(true, '字典缓存清除成功');
        }
    }
}