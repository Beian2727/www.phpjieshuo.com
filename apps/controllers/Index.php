<?php
/**
 * 首页。
 * @author fingerQin
 * @date 2016-09-07
 */

use services\CategoryService;

class IndexController extends \common\controllers\User
{
    public function indexAction() 
    {
        $catList = CategoryService::getByParentToCategory(0, CategoryService::CAT_DOC);
        $this->assign('catList', $catList);
    }
}