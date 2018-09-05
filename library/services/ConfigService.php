<?php
/**
 * 系统配置管理。
 * @author fingerQin
 * @date 2016-01-29
 */

namespace services;

use finger\Validator;
use common\YCore;
use models\Config;

class ConfigService extends AbstractService
{
    /**
     * 以键值对形式返回所有的配置数据。
     * 
     * @param bool $isReadCache 是否从缓存读取配置数据。用于管理后台读取实时配置。
     *
     * @return array
     */
    public static function getAllConfig($isReadCache = true)
    {
        $configCacheKey = 'config_service_system_configs';
        $configs        = \Yaf_Registry::get($configCacheKey);
        if ($isReadCache === true && $configs !== null && $configs !== false) { // 保证每个请求只会调用一次Redis读取缓存的操作，节省Redis资源。
            return $configs;
        }
        $cache        = YCore::getCache();
        $configsCache = $cache->get($configCacheKey);
        if ($isReadCache === false || $configsCache === false) {
            $ConfigModel = new Config();
            $columns = ['cname', 'cvalue'];
            $where   = [
                'status' => 1
            ];
            $orderBy = ' config_id ASC ';
            $result  = $ConfigModel->fetchAll($columns, $where, 0, $orderBy);
            $configs = [];
            foreach ($result as $val) {
                $configs[$val['cname']] = $val['cvalue'];
            }
            $cache->set($configCacheKey, json_encode($configs));
            \Yaf_Registry::set($configCacheKey, $configs);
            return $configs;
        } else {
            $configs = json_decode($configsCache, true);
            \Yaf_Registry::set($configCacheKey, $configs);
            return $configs;
        }
    }

    /**
     * 以键值对形式返回所有的配置数据(直读数据库版)。
     * 
     * @return array
     */
    public static function directReadDbConfig()
    {
        // 先从当前请求中拿已经放入此中的配置数据。
        $configCacheKey = 'config_service_system_configs';
        $configs        = \Yaf_Registry::get($configCacheKey);
        if ($configs !== null && $configs !== false) { // 保证每个请求只会调用一次Redis读取缓存的操作，节省Redis资源。
            return $configs;
        }
        $ConfigModel = new Config();
        $columns = ['cname', 'cvalue'];
        $where   = [
            'status' => 1
        ];
        $orderBy = ' config_id ASC ';
        $result   = $ConfigModel->fetchAll($columns, $where, 0, $orderBy);
        $configs  = [];
        foreach ($result as $val) {
            $configs[$val['cname']] = $val['cvalue'];
        }
        \Yaf_Registry::set($configCacheKey, $configs);
        return $configs;
    }

    /**
     * 清除配置文件缓存。
     *
     * @return void
     */
    public static function clearConfigCache()
    {
        $configCacheKey = 'config_service_system_configs';
        $cache = YCore::getCache();
        $cache->delete($configCacheKey);
        \Yaf_Registry::del($configCacheKey);
    }

    /**
     * 获取配置列表。
     *
     * @param  string  $keyword  查询关键词。
     * @param  int     $page     当前页码。
     * @param  int     $count    每页显示条数。
     * @return array
     */
    public static function getConfigList($keyword = '', $page, $count)
    {
        $ConfigModel = new Config();
        return $ConfigModel->getConfigList($keyword, $page, $count);
    }

    /**
     * 添加配置。
     *
     * @param  int      $adminId       管理员ID。
     * @param  string   $ctitle        配置标题。
     * @param  string   $cname         配置名称。
     * @param  string   $cvalue        配置值。
     * @param  string   $description   配置描述。
     * @return void
     */
    public static function addConfig($adminId, $ctitle, $cname, $cvalue, $description)
    {
        // [1] 验证
        $data = [
            'ctitle' => $ctitle,
            'cname'  => $cname,
            'cvalue' => $cvalue,
            'desc'   => $description
        ];
        $rules = [
            'ctitle' => '配置标题|require|len:1:50:1',
            'cname'  => '配置名称|require|alpha_dash|len:1:30:0',
            'cvalue' => '配置值|len:1:1000:1',
            'desc'   => '配置描述|len:0:255:1'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $ConfigModel = new Config();
        $configId    = $ConfigModel->addConfig($adminId, $ctitle, $cname, $cvalue, $description);
        if ($configId == 0) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearConfigCache();
        unset($data, $rules, $configId, $ConfigModel);
    }

    /**
     * 修改配置。
     *
     * @param  int      $adminId       管理员ID。
     * @param  int      $configId      配置ID。
     * @param  string   $ctitle        配置标题。
     * @param  string   $cname         配置名称。
     * @param  string   $cvalue        配置值。
     * @param  string   $description   配置描述。
     * @return void
     */
    public static function editConfig($adminId, $configId, $ctitle, $cname, $cvalue, $description)
    {
        // [1] 验证
        $data = [
            'ctitle' => $ctitle,
            'cname'  => $cname,
            'cvalue' => $cvalue,
            'desc'   => $description
        ];
        $rules = [
            'ctitle' => '配置标题|require|len:1:50:1',
            'cname'  => '配置名称|require|alpha_dash|len:1:30:0',
            'cvalue' => '配置值|len:1:255:1',
            'desc'   => '配置描述|len:0:255:1'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $ConfigModel = new Config();
        $where = [
            'config_id' => $configId,
            'status'    => Config::STATUS_NORMAL
        ];
        $configDetail = $ConfigModel->fetchOne([], $where);
        if (empty($configDetail)) {
            YCore::exception(STATUS_ERROR, '该配置不存在');
        }
        unset($data, $rules);
        self::clearConfigCache();
        $ok = $ConfigModel->editConfig($configId, $adminId, $ctitle, $cname, $cvalue, $description);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 按配置编码更新配置值。
     *
     * @param  string $cname     配置编码。
     * @param  string $cvalue    配置值。
     * @return void
     */
    public static function updateConfigValue($cname, $cvalue)
    {
        $ConfigModel = new Config();
        if (!Validator::is_len($cvalue, 1, 255, true)) {
            YCore::exception(STATUS_ERROR, '配置值必须小于255个字符');
        }
        $update = [
            'cvlaue'        => $cvalue,
            'modified_by'   => 0,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'cname'  => $cname,
            'status' => Config::STATUS_NORMAL
        ];
        $ok = $ConfigModel->update($update, $where);
        if (! $ok) {
            YCore::exception(STATUS_SERVER_ERROR, '配置更新失败');
        }
        self::clearConfigCache();
    }

    /**
     * 删除配置。
     *
     * @param  int $adminId     管理员ID。
     * @param  int $configId    配置ID。
     * @return void
     */
    public static function deleteConfig($adminId, $configId)
    {
        $ConfigModel = new Config();
        $where = [
            'config_id' => $configId,
            'status'    => Config::STATUS_NORMAL
        ];
        $configDetail = $ConfigModel->fetchOne([], $where);
        if (empty($configDetail) || $configDetail['status'] != 1) {
            YCore::exception(STATUS_ERROR, '配置不存在或已经删除');
        }
        $data = [
            'status'        => Config::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        self::clearConfigCache();
        $ok = $ConfigModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 获取配置详情。
     *
     * @param  int  $configId 配置ID。
     * @return array
     */
    public static function getConfigDetail($configId)
    {
        $ConfigModel = new Config();
        $detail = $ConfigModel->fetchOne([], ['config_id' => $configId]);
        if (empty($detail) || $detail['status'] != 1) {
            YCore::exception(STATUS_ERROR, '配置不存在或已经删除');
        }
        return $detail;
    }
}
