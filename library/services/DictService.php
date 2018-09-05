<?php
/**
 * 系统字典管理。
 * @author fingerQin
 * @date 2015-11-10
 */

namespace services;

use finger\Validator;
use common\YCore;
use models\Dict;
use models\DictType;

class DictService extends AbstractService
{
    /**
     * 获取系统所有的字典类型数据。
     *
     * @return array
     */
    private static function getSystemAllDictType()
    {
        $cacheKey    = 'dict_service_system_dict_type';
        $allDictType = \Yaf_Registry::get($cacheKey);
        if ($allDictType !== null && $allDictType !== false) {
            return $allDictType;
        }
        $cache  = YCore::getCache();
        $result = $cache->get($cacheKey);
        if ($result === false) {
            $dictTypeModel = new DictType();
            $columns       = ['dict_type_id', 'type_code'];
            $result        = $dictTypeModel->fetchAll();
            $allDictType   = [];
            foreach ($result as $dict_type) {
                $allDictType[$dict_type['type_code']] = $dict_type['dict_type_id'];
            }
            $cache->set($cacheKey, json_encode($allDictType));
            \Yaf_Registry::set($cacheKey, $allDictType);
            return $allDictType;
        } else {
            $allDictType = json_decode($result, true);
            \Yaf_Registry::set($cacheKey, $allDictType);
            return $allDictType;
        }
    }

    /**
     * 获取系统所有字典值。
     * -- 1、上万条字典值内存占用也才20KB左右。
     *
     * @return string
     */
    private static function getSystemDictTypeValue()
    {
        $cacheKey = 'dict_service_system_dict_type_value';
        $allDictTypeValue = \Yaf_Registry::get($cacheKey);
        if ($allDictTypeValue !== null && $allDictTypeValue !== false) {
            return $allDictTypeValue;
        }
        $cache  = YCore::getCache();
        $result = $cache->get($cacheKey);
        if ($result === false) {
            $DictModel = new Dict();
            $columns = [
                'dict_type_id',
                'dict_code',
                'dict_value'
            ];
            $where = [
                'status' => 1
            ];
            $orderBy = 'listorder ASC, dict_id ASC';
            $result  = $DictModel->fetchAll($columns, $where, 0, $orderBy);
            $allDictTypeValue = [];
            foreach ($result as $dict) {
                $allDictTypeValue[$dict['dict_type_id']][$dict['dict_code']] = $dict['dict_value'];
            }
            $cache->set($cacheKey, json_encode($allDictTypeValue));
            \Yaf_Registry::set($cacheKey, $allDictTypeValue);
            return $allDictTypeValue;
        } else {
            $allDictTypeValue = json_decode($result, true);
            \Yaf_Registry::set($cacheKey, $allDictTypeValue);
            return $allDictTypeValue;
        }
    }

    /**
     * 清理所有字典相关的缓存数据。
     *
     * @return void
     */
    public static function clearDictCache()
    {
        // [1] 清理字典类型数据缓存。
        $configCacheKey = 'dict_service_system_dict_type';
        $cache = YCore::getCache();
        $cache->delete($configCacheKey);
        \Yaf_Registry::del($configCacheKey);
        // [2] 清理字典值数据缓存。
        $configCacheKey = 'dict_service_system_dict_type_value';
        $cache->delete($configCacheKey);
        \Yaf_Registry::del($configCacheKey);
    }

    /**
     * 获取系统字典数据。
     *
     * @param  string $dictTypeCode    字典类型编码。
     * @param  string $dictCode        字典编码。
     * @return array
     */
    public static function getSystemDict($dictTypeCode, $dictCode = '')
    {
        // [1] 获取所有字典类型值。
        $allDictType = self::getSystemAllDictType();
        if (!isset($allDictType[$dictTypeCode])) {
            YCore::exception(STATUS_SERVER_ERROR, "系统字典[{$dictTypeCode}]未设置");
        }
        $dictTypeId     = $allDictType[$dictTypeCode];
        $dictTypeValues = self::getSystemDictTypeValue();
        $values           = $dictTypeValues[$dictTypeId];
        if (strlen($dictCode) > 0) {
            foreach ($values as $_dictCode => $_dictValue) {
                if ($_dictCode == $dictCode) {
                    return $_dictValue;
                }
            }
            YCore::exception(STATUS_SERVER_ERROR, "字典值编码[{$dictCode}]不存在");
        } else {
            return $values;
        }
    }

    /**
     * 字典排序。
     *
     * @param  int   $adminId    管理员ID。
     * @param  array $listorders 排序。字典值ID=>排序位置。
     * @return boolean
     */
    public static function sortDict($adminId, $listorders)
    {
        if (empty($listorders)) {
            YCore::exception(STATUS_ERROR, '没有任何排序数据');
        }
        foreach ($listorders as $dictId => $sort) {
            if (!Validator::is_integer($dictId) || $dictId < 0) {
                YCore::exception(STATUS_ERROR, '非法参数');
            }
            if (!Validator::is_integer($sort) || $sort < 0) {
                YCore::exception(STATUS_ERROR, '非法参数');
            }
            $DictModel = new Dict();
            $DictModel->sort($adminId, $dictId, $sort);
        }
        self::clearDictCache();
        return true;
    }

    /**
     * 获取字典类型列表。
     *
     * @param  string    $keyword    查询关键词。
     * @param  int       $page       当前页码。
     * @param  int       $count      每页显示条数。
     * @return array
     */
    public static function getDictTypeList($keyword = '', $page, $count)
    {
        $dictTypeModel = new DictType();
        return $dictTypeModel->getDictTypeList($keyword, $page, $count);
    }

    /**
     * 获取字典列表。
     *
     * @param  int     $dictTypeId     字典类型ID。
     * @param  string  $keywords       查询关键词。查询值编码或值名称。
     * @param  int     $page           当前页码。
     * @param  int     $count          每页显示条数。
     * @return array
     */
    public static function getDictList($dictTypeId, $keywords, $page, $count)
    {
        $DictModel = new Dict();
        return $DictModel->getDictList($dictTypeId, $keywords, $page, $count);
    }

    /**
     * 获取字典详情。
     *
     * @param  int  $dictId 字典ID。
     * @return array
     */
    public static function getDict($dictId)
    {
        $DictModel = new Dict();
        $dict = $DictModel->getDict($dictId);
        if (empty($dict) || $dict['status'] != 1) {
            YCore::exception(STATUS_ERROR, '字典不存在或已经删除');
        }
        return $dict;
    }

    /**
     * 获取字典类型详情。
     *
     * @param  int  $dictTypeId 字典类型ID。
     * @return array
     */
    public static function getDictType($dictTypeId)
    {
        $dictTypeModel  = new DictType();
        $dictTypeDetail = $dictTypeModel->getDictTypeDetail($dictTypeId);
        if (empty($dictTypeDetail) || $dictTypeDetail['status'] != 1) {
            YCore::exception(STATUS_ERROR, '字典类型不存在或已经删除');
        }
        return $dictTypeDetail;
    }

    /**
     * 添加字典类型。
     *
     * @param  int      $adminId       修改人ID（管理员ID）。
     * @param  string   $typeCode      字典类型code编码。
     * @param  string   $typeName      字典类型名称。
     * @param  string   $description   字典类型描述。
     * @return void
     */
    public static function addDictType($adminId, $typeCode, $typeName, $description)
    {
        // [1] 验证
        $data = [
            'type_code' => $typeCode,
            'type_name' => $typeName
        ];
        $rules = [
            'type_code' => '字典类型编码|require|alpha_dash|len:1:50:0',
            'type_name' => '字典类型名称|require|len:1:50:0'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $dictTypeModel = new DictType();
        $dictTypeId    = $dictTypeModel->addDictType($adminId, $typeCode, $typeName, $description);
        if ($dictTypeId == 0) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }

    /**
     * 编辑字典类型。
     *
     * @param  int     $adminId        修改人ID（管理员ID）。
     * @param  int     $dictTypeId     字典类型ID。
     * @param  string  $typeCode       字典类型code编码。
     * @param  string  $typeName       字典类型名称。
     * @param  string  $description    字典类型描述。
     * @return void
     */
    public static function editDictType($adminId, $dictTypeId, $typeCode, $typeName, $description)
    {
        // [1] 验证
        $data = [
            'type_code' => $typeCode,
            'type_name' => $typeName
        ];
        $rules = [
            'type_code' => '字典类型编码|require|alpha_dash|len:1:50:0',
            'type_name' => '字典类型名称|require|len:1:50:0'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $dictTypeModel  = new DictType();
        $dictTypeDetail = $dictTypeModel->getDictTypeDetail($dictTypeId);
        if (empty($dictTypeDetail)) {
            YCore::exception(STATUS_ERROR, '字典类型不存在或已经删除');
        }
        $ok = $dictTypeModel->editDictType($adminId, $dictTypeId, $typeCode, $typeName, $description);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }

    /**
     * 字典类型删除。
     *
     * @param  int $adminId     管理员ID。
     * @param  int $dictTypeId  字典类型ID。
     * @return void
     */
    public static function deleteDictType($adminId, $dictTypeId)
    {
        $dictTypeModel  = new DictType();
        $dictTypeDetail = $dictTypeModel->getDictTypeDetail($dictTypeId);
        if (empty($dictTypeDetail)) {
            YCore::exception(STATUS_ERROR, '字典类型不存在或已经删除');
        }
        $DictModel = new Dict();
        $isEmpty   = $DictModel->isNotEmpty($dictTypeId);
        if (!$isEmpty) {
            YCore::exception(STATUS_ERROR, '该字典的值不为空,请先清空再删除该字典');
        }
        $ok = $dictTypeModel->deleteDictType($adminId, $dictTypeId);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }

    /**
     * 添加字典。
     *
     * @param  int       $dictTypeId    字典类型ID。
     * @param  string    $dictCode      字典编码。
     * @param  string    $dictvalue     字典值。
     * @param  string    $description   描述。
     * @param  int       $listorder     排序。
     * @param  int       $adminId       管理ID。
     * @return void
     */
    public static function addDict($dictTypeId, $dictCode, $dictvalue, $description, $listorder, $adminId)
    {
        // [1] 验证
        $data = [
            'dict_type_id' => $dictTypeId,
            'dict_code'    => $dictCode,
            'dict_value'   => $dictvalue,
            'description'  => $description,
            'listorder'    => $listorder
        ];
        $rules = [
            'dict_type_id' => '字典类型ID|require|integer',
            'dict_code'    => '字典编码|require|alpha_dash|len:1:50:0',
            'dict_value'   => '字典值|require|len:1:50:1',
            'description'  => '字典描述|require|len:1:200:1',
            'listorder'    => '排序|require|integer'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $DictModel  = new Dict();
        $dictDetail = $DictModel->fetchOne([], ['dict_code' => $dictCode, 'dict_type_id' => $dictTypeId, 'status' => Dict::STATUS_NORMAL]);
        if ($dictDetail) {
            YCore::exception(STATUS_ERROR, '不要重复添加');
        }
        $dictId = $DictModel->addDict($adminId, $dictTypeId, $dictCode, $dictvalue, $description, $listorder);
        if ($dictId == 0) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }

    /**
     * 编辑字典。
     *
     * @param  int      $dictId       字典ID。
     * @param  string   $dictCode     字典编码。
     * @param  string   $dictvalue    字典值。
     * @param  string   $description  描述。
     * @param  int      $listorder    排序。
     * @param  int      $adminId      管理员ID。
     * @return void
     */
    public static function editDict($dictId, $dictCode, $dictvalue, $description, $listorder, $adminId)
    {
        // [1] 验证
        $data = [
            'dict_id'     => $dictId,
            'dict_code'   => $dictCode,
            'dict_value'  => $dictvalue,
            'description' => $description,
            'listorder'   => $listorder
        ];
        $rules = [
            'dict_id'     => '字典ID|require|integer',
            'dict_code'   => '字典编码|require|alpha_dash|len:1:50:0',
            'dict_value'  => '字典值|require|len:1:50:1',
            'description' => '字典描述|require|len:1:200:1',
            'listorder'   => '排序|require|integer'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $DictModel  = new Dict();
        $dictDetail = $DictModel->getDict($dictId);
        if (empty($dictDetail) || $dictDetail['status'] == 2) {
            YCore::exception(STATUS_ERROR, '字典不存在');
        }
        $ok = $DictModel->editDict($dictId, $adminId, $dictCode, $dictvalue, $description, $listorder);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }

    /**
     * 删除字典。
     *
     * @param  int $dictId  字典ID。
     * @param  int $adminId 管理员ID。
     * @return void
     */
    public static function deleteDict($dictId, $adminId)
    {
        $DictModel  = new Dict();
        $dictDetail = $DictModel->getDict($dictId);
        if (empty($dictDetail) || $dictDetail['status'] == 2) {
            YCore::exception(STATUS_ERROR, '字典不存在');
        }
        $ok = $DictModel->deleteDict($dictId, $adminId);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        self::clearDictCache();
    }
}
