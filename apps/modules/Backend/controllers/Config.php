<?php
/**
 * 配置管理。
 * @author fingerQin
 * @date 2016-01-14
 */

use services\ConfigService;
use common\YCore;
use finger\Paginator;

class ConfigController extends \common\controllers\Admin
{
    /**
     * 配置列表。
     */
    public function indexAction()
    {
        $keywords  = $this->getString('keywords', '');
        $page      = $this->getInt(YCore::appconfig('pager'), 1);
        $list      = ConfigService::getConfigList($keywords, $page, 20);
        $paginator = new Paginator($list['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('keywords', $keywords);
        $this->assign('list', $list['list']);
    }

    /**
     * 添加配置。
     */
    public function addAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $ctitle      = $this->getString('ctitle');
            $cname       = $this->getString('cname');
            $cvalue      = $this->getString('cvalue');
            $description = $this->getString('description');
            ConfigService::addConfig($this->admin_id, $ctitle, $cname, $cvalue, $description);
            $this->json(true, '添加成功');
        }
    }

    /**
     * 配置编辑。
     */
    public function editAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $configId    = $this->getInt('config_id');
            $ctitle      = $this->getString('ctitle');
            $cname       = $this->getString('cname');
            $cvalue      = $this->getString('cvalue');
            $description = $this->getString('description');
            ConfigService::editConfig($this->admin_id, $configId, $ctitle, $cname, $cvalue, $description);
            $this->json(true, '修改成功');
        }
        $configId = $this->getInt('config_id');
        $detail   = ConfigService::getConfigDetail($configId);
        $this->assign('detail', $detail);
    }

    /**
     * 配置删除。
     */
    public function deleteAction()
    {
        $configId = $this->getInt('config_id');
        ConfigService::deleteConfig($this->admin_id, $configId);
        $this->json(true, '删除成功');
    }

    /**
     * 清除配置缓存。
     */
    public function clearCacheAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            ConfigService::clearConfigCache();
            $this->json(true, '配置缓存清除成功');
        }
    }
}