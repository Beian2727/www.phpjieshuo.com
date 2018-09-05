<?php
/**
 * 分类表。
 * @author fingerQin
 * @date 2016-03-25
 */

namespace models;

class Category extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_category';

    /**
     * 设置分类排序值。
     *
     * @param  int      $catId     分类ID。
     * @param  array    $sortVal   排序值。
     * @return bool
     */
    public function sort($catId, $sortVal)
    {
        $data = [
            'listorder'     => $sortVal,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $where = [
            'cat_id' => $catId
        ];
        return $this->update($data, $where);
    }
} 