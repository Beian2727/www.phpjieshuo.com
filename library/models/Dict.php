<?php
/**
 * 字典数据表。
 * @author fingerQin
 * @date 2015-11-10
 */

namespace models;

class Dict extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_dict';

    /**
     * 字典类型的字典值是否为空。
     *
     * @param  int $dictTypeId 字典类型ID。
     * @return bool true:空、false:非空。
     */
    public function isNotEmpty($dictTypeId)
    {
        $where = [
            'dict_type_id' => $dictTypeId,
            'status'       => self::STATUS_NORMAL
        ];
        $count = $this->count($where);
        return $count > 0 ? false : true;
    }

    /**
     * 获取字典列表。
     *
     * @param  int      $dictTypeId 字典类型ID。
     * @param  string   $keywords   查询关键词。查询值编码或名称。
     * @param  int      $page       当前页码。
     * @param  int      $count      每页显示条数。
     * @return array
     */
    public function getDictList($dictTypeId, $keywords = '', $page = 1, $count = 10)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = " dict_id,dict_type_id,dict_code,dict_value,description,listorder ";
        $where   = ' WHERE status = :status AND dict_type_id = :dict_type_id ';
        $params  = [
            ':status'       => self::STATUS_NORMAL,
            ':dict_type_id' => $dictTypeId
        ];
        if (strlen($keywords) > 0) {
            $where .= ' AND (dict_code LIKE :dict_code OR dict_value LIKE :dict_value )';
            $params[':dict_code']  = "%{$keywords}%";
            $params[':dict_value'] = "%{$keywords}%";
        }
        $orderBy   = ' ORDER BY listorder ASC,dict_id ASC ';
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
     * 获取字典数据。
     *
     * @param  int $dictId 字典ID。
     * @return array
     */
    public function getDict($dictId)
    {
        $columnds = [
            'dict_id', 'dict_value', 'dict_type_id',
            'dict_code', 'description', 'listorder', 'status'
        ];
        $data = $this->fetchOne($columnds, ['dict_id' => $dictId]);
        return empty($data) ? [] : $data;
    }

    /**
     * 获取字典值。
     *
     * @param  int $dictTypeId 字典类型ID。
     * @return array|null
     */
    public function getValues($dictTypeId)
    {
        $where = [
            'status'       => self::STATUS_NORMAL,
            'dict_type_id' => $dictTypeId
        ];
        $column = [
            'dict_value', 'dict_code'
        ];
        $order  = 'listorder ASC, dict_id ASC';
        $result = $this->fetchAll($column, $where, 0, $order);
        if ($result) {
            $data = [];
            foreach ($result as $val) {
                $data[$val['dict_code']] = $val['dict_value'];
            }
            return $data;
        } else {
            return null;
        }
    }

    /**
     * 添加字典值。
     *
     * @param  int      $adminId        管理员ID。
     * @param  int      $dictTypeId     字典类型ID。
     * @param  string   $dictCode       字典编码。
     * @param  string   $dictValue      字典名称。
     * @param  string   $description    字典描述。
     * @param  int      $listorder      排序。
     * @return bool
     */
    public function addDict($adminId, $dictTypeId, $dictCode, $dictValue, $description = '', $listorder = 0)
    {
        $data = [
            'dict_type_id' => $dictTypeId,
            'dict_code'    => $dictCode,
            'dict_value'   => $dictValue,
            'description'  => $description,
            'listorder'    => $listorder,
            'status'       => self::STATUS_NORMAL,
            'created_by'   => $adminId,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $dictId = $this->insert($data);
        return $dictId ? true : false;
    }

    /**
     * 编辑字典值。
     *
     * @param  int      $dictId         字典值ID。
     * @param  int      $adminId        管理员ID。
     * @param  string   $dictCode       字典编码。
     * @param  string   $dictValue      字典值。
     * @param  string   $description    字典描述。
     * @param  int      $listorder      排序。
     * @return bool
     */
    public function editDict($dictId, $adminId, $dictCode, $dictValue, $description = '', $listorder = 0)
    {
        $data = [
            'dict_code'     => $dictCode,
            'dict_value'    => $dictValue,
            'description'   => $description,
            'listorder'     => $listorder,
            'status'        => self::STATUS_NORMAL,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = ['dict_id' => $dictId];
        $ok    = $this->update($data, $where);
        return $ok ? true : false;
    }

    /**
     * 删除字典值。
     *
     * @param  int  $dictId  字典值ID。
     * @param  int  $adminId 管理员ID。
     * @return bool
     */
    public function deleteDict($dictId, $adminId)
    {
        $data = [
            'status'        => self::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'dict_id' => $dictId
        ];
        $ok = $this->update($data, $where);
        return $ok ? true : false;
    }

    /**
     * 设置字段值排序。
     *
     * @param  int  $adminId    管理员ID。
     * @param  int  $dictId     字段ID。
     * @param  int  $sort       排序值。
     * @return bool
     */
    public function sort($adminId, $dictId, $sort)
    {
        $data = [
            'modified_by'   => $adminId,
            'listorder'     => $sort,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'dict_id' => $dictId
        ];
        $ok = $this->update($data, $where);
        return $ok ? true : false;
    }
}
