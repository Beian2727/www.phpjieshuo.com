<?php
/**
 * 菜单管理。
 * -- 1、菜单最多允许三层。超过三层已经没有多大意义。并不便于管理。
 * @author fingerQin
 * @date 2015-11-26
 */

use services\AdminPermissionService;

class MenuController extends \common\controllers\Admin
{
    /**
     * 菜单列表。
     */
    public function indexAction()
    {
        $list = AdminPermissionService::getMenuList(0);
        $this->assign('list', $list);
    }

    /**
     * 添加菜单。
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $parentid   = $this->getInt('parentid', 0);
            $name       = $this->getString('name');
            $ctrlName   = $this->getString('c');
            $actionName = $this->getString('a');
            $data       = $this->getString('data', '');
            $listorder  = $this->getInt('listorder', 0);
            $display    = $this->getInt('display', 0);
            AdminPermissionService::addMenu($parentid, $name, $ctrlName, $actionName, $data, $listorder, $display);
            $this->json(true, '添加成功');
        }
        $parentid = $this->getInt('parentid', 0);
        $list     = AdminPermissionService::getMenuList(0);
        $this->assign('list', $list);
        $this->assign('parentid', $parentid);
    }

    /**
     * 编辑菜单。
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $menuId    = $this->getInt('menu_id');
            $parentid   = $this->getString('parentid');
            $name       = $this->getString('name');
            $ctrlName   = $this->getString('c');
            $actionName = $this->getString('a');
            $data       = $this->getString('data');
            $listorder  = $this->getInt('listorder', 0);
            $display    = $this->getInt('display', 0);
            AdminPermissionService::editMenu($menuId, $parentid, $name, $ctrlName, $actionName, $data, $listorder, $display);
            $this->json(true, '编辑成功');
        }
        $menuId   = $this->getInt('menu_id');
        $detail   = AdminPermissionService::getMenuDetail($menuId);
        $parentid = $this->getInt('parentid', 0);
        $list     = AdminPermissionService::getMenuList(0);
        $this->assign('detail', $detail);
        $this->assign('list', $list);
    }

    /**
     * 删除菜单。
     */
    public function deleteAction()
    {
        $menuId = $this->getInt('menu_id');
        AdminPermissionService::deleteMenu($menuId);
        $this->json(true, '删除成功');
       
    }

    /**
     * 菜单排序。
     */
    public function sortAction()
    {
        if ($this->_request->isPost()) {
            $listorders = $this->getArray('listorders');
            AdminPermissionService::sortMenu($listorders);
            $this->json(true, '排序成功');
        }
    }
}