<?php
/**
 * 字典类型表。
 * @author fingerQin
 * @date 2015-11-10
 */

namespace models;

use common\YCore;

class DictType extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_dict_type';

    /**
     * 获取字典类型列表。
     *
     * @param  string   $keyword    查询关键词。模糊搜索字典类型编码、字典类型名称。
     * @param  int      $page       页码。
     * @param  int      $count      每页显示条数。
     * @return array
     */
    public function getDictTypeList($keyword = '', $page = 1, $count = 10)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = ' dict_type_id,type_code,type_name,description ';
        $where   = ' WHERE status = :status ';
        $params  = [
            ':status' => self::STATUS_NORMAL
        ];
        if (strlen($keyword) > 0) {
            $where .= ' AND ( type_code LIKE :type_code OR type_name LIKE :type_name )';
            $params[':type_code'] = "%{$keyword}%";
            $params[':type_name'] = "%{$keyword}%";
        }
        $orderBy   = ' ORDER BY dict_type_id ASC ';
        $sql       = "SELECT COUNT(1) AS count FROM {$this->tableName} {$where}";
        $countData = $this->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM {$this->tableName} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $this->rawQuery($sql, $params)->rawFetchAll();
        $result    = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => $this->IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 获取字典类型信息。
     *
     * @param  int   $dictTypeId 字典类型ID。
     * @return array
     */
    public function getDictTypeDetail($dictTypeId)
    {
        $data = $this->fetchOne([], ['dict_type_id' => $dictTypeId]);
        return empty($data) ? [] : $data;
    }

    /**
     * 删除字典类型数据。
     *
     * @param  int  $adminId    修改人ID。管理员ID。
     * @param  int  $dictTypeId 字典类型ID。
     * @return bool
     */
    public function deleteDictType($adminId, $dictTypeId)
    {
        $where = [
            'dict_type_id' => $dictTypeId
        ];
        $data = [
            'status'        => self::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        return $this->update($data, $where);
    }

    /**
     * 添加字典类型。
     *
     * @param  int      $adminId        修改人ID（管理员ID）。
     * @param  string   $typeCode       字典类型code编码。
     * @param  string   $typeName       字典类型名称。
     * @param  string   $description    字典类型描述。
     * @return bool
     */
    public function addDictType($adminId, $typeCode, $typeName, $description)
    {
        $where = [
            'type_code' => $typeCode,
            'status'    => self::STATUS_NORMAL
        ];
        $dict_type_detail = $this->fetchOne([], $where);
        if (! empty($dict_type_detail)) {
            YCore::exception(-1, '字典编码已经存在,请不要重复添加');
        }
        $data = [
            'type_code'    => $typeCode,
            'type_name'    => $typeName,
            'description'  => $description,
            'created_by'   => $adminId,
            'status'       => self::STATUS_NORMAL,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $id = $this->insert($data);
        return $id ? true : false;
    }

    /**
     * 编辑字典类型。
     *
     * @param  int      $adminId        修改人ID（管理员ID）。
     * @param  int      $dictTypeId     字典类型ID。
     * @param  string   $typeCode       字典类型code编码。
     * @param  string   $typeName       字典类型名称。
     * @param  string   $description    字典类型描述。
     * @return bool
     */
    public function editDictType($adminId, $dictTypeId, $typeCode, $typeName, $description)
    {
        $data = [
            'type_code'     => $typeCode,
            'type_name'     => $typeName,
            'description'   => $description,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'dict_type_id' => $dictTypeId
        ];
        return $this->update($data, $where);
    }
}
