<?php
/**
 * 系统配置表。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace models;

use common\YCore;

class Config extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_config';

    /**
     * 获取配置值。
     *
     * @param  string $cname 配置名称。
     * @return string|null
     */
    public function getValue($cname)
    {
        $data = $this->fetchOne(['cvalue'], ['cname' => $cname]);
        return $data ? $data['cvalue'] : null;
    }

    /**
     * 获取配置列表。
     *
     * @param  string   $keyword    查询关键词。模糊搜索字典类型编码、字典类型名称。
     * @param  int      $page       页码。
     * @param  int      $count      每页显示条数。
     * @return array
     */
    public function getConfigList($keyword = '', $page = 1, $count = 10)
    {
        $offset  = $this->getPaginationOffset($page, $count);
        $columns = " config_id,ctitle,cname,cvalue AS cvalue,description,created_time,modified_time ";
        $where   = ' WHERE status = :status ';
        $params  = [
            ':status' => self::STATUS_NORMAL
        ];
        if (strlen($keyword) > 0) {
            $where .= ' AND ( ctitle LIKE :ctitle OR cname LIKE :cname )';
            $params[':ctitle'] = "%{$keyword}%";
            $params[':cname']  = "%{$keyword}%";
        }
        $orderBy   = ' ORDER BY config_id ASC ';
        $sql       = "SELECT COUNT(1) AS count FROM {$this->tableName} {$where}";
        $countData = $this->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM {$this->tableName} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $this->rawQuery($sql, $params)->rawFetchAll();
        foreach ($list as $k => $item) {
            $item['modified_time'] = YCore::formatDateTime($item['modified_time']);
            $list[$k] = $item;
        }
        $result = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => $this->IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 添加配置。
     *
     * @param  int     $adminId       管理员ID。
     * @param  string  $ctitle        配置标题。
     * @param  string  $cname         配置名称。
     * @param  string  $cvalue        配置值。
     * @param  string  $description   配置描述。
     * @return bool
     */
    public function addConfig($adminId, $ctitle, $cname, $cvalue, $description)
    {
        $data = [
            'ctitle'       => $ctitle,
            'cname'        => $cname,
            'cvalue'       => $cvalue,
            'description'  => $description,
            'status'       => self::STATUS_NORMAL,
            'created_by'   => $adminId,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $configId = $this->insert($data);
        return $configId ? true : false;
    }

    /**
     * 编辑配置。
     *
     * @param  int     $configId     配置ID。
     * @param  int     $adminId      管理员ID。
     * @param  string  $ctitle       配置标题。
     * @param  string  $cname        配置名称。
     * @param  string  $cvalue       配置值。
     * @param  string  $description  配置描述。
     * @return bool
     */
    public function editConfig($configId, $adminId, $ctitle, $cname, $cvalue, $description)
    {
        $data = [
            'ctitle'        => $ctitle,
            'cname'         => $cname,
            'cvalue'        => $cvalue,
            'description'   => $description,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'config_id' => $configId
        ];
        return $this->update($data, $where);
    }

    /**
     * 删除配置。
     *
     * @param  int $configId 配置ID。
     * @param  int $adminId  管理员ID。
     * 
     * @return bool
     */
    public function deleteConfig($configId, $adminId)
    {
        $data = [
            'status'        => self::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => time()
        ];
        $where = [
            'config_id' => $configId
        ];
        return $this->update($data, $where);
    }
}
