<?php
/**
 * 默认controller。
 * @author fingerQin
 * @date 2015-11-13
 */

use services\AdminPermissionService;
use services\UploadService;
use common\YCore;
use common\YUrl;

class IndexController extends \common\controllers\Admin
{
    /**
     * 首页。
     */
    public function indexAction()
    {
        $topMenu = AdminPermissionService::getRoleSubMenu($this->roleid, 0);
        $this->assign('realname', $this->realname);
        $this->assign('username', $this->username);
        $this->assign('mobilephone', $this->mobilephone);
        $this->assign('top_menu', $topMenu);
    }

    /**
     * 取左侧菜单。
     */
    public function leftMenuAction()
    {
        $menuId   = $this->getInt('menu_id');
        $leftMenu = AdminPermissionService::getAdminLeftMenu($this->roleid, $menuId);
        $this->assign('left_menu', $leftMenu);
    }

    /**
     * 位置（当前页面所处菜单位置）。
     */
    public function arrowAction()
    {
        $menuId = $this->getInt('menu_id');
        echo AdminPermissionService::getMenuCrumbs($menuId);
        $this->end();
    }

    /**
     * 默认内容页。
     */
    public function rightAction()
    {
    }

    /**
     * 文件上传。
     */
    public function uploadAction()
    {
        header("Access-Control-Allow-Origin: *");
        $result = UploadService::uploadImage(1, $this->admin_id, 'voucher', 2, 'uploadfile');
        $this->json(true, '上传成功', $result);
        $this->end();
    }
}