<?php
/**
 * 后台菜单表。
 * @author fingerQin
 * @date 2015-11-17
 */

namespace models;

class Menu extends AbstractBase
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_menu';

    /**
     * 获取菜单信息。
     *
     * @param  int $menuId 菜单ID。
     * @return array
     */
    public function getMenu($menuId)
    {
        $where = [
            'menu_id' => $menuId
        ];
        return $this->fetchOne([], $where);
    }

    /**
     * 获取所有的菜单。
     *
     * @return array
     */
    public function getAllMenu()
    {
        return $this->fetchAll([], [], 0, 'menu_id ASC, listorder ASC');
    }

    /**
     * 删除菜单。
     *
     * @param  int $menuId 菜单ID。
     * @return bool
     */
    public function deleteMenu($menuId)
    {
        $where = [
            'menu_id' => $menuId
        ];
        return $this->delete($where);
    }

    /**
     * 设置菜单排序值。
     *
     * @param  int   $menuId  菜单ID。
     * @param  array $sortVal 排序值。
     * @return bool
     */
    public function sortMenu($menuId, $sortVal)
    {
        $data = [
            'listorder' => $sortVal
        ];
        $where = [
            'menu_id' => $menuId
        ];
        return $this->update($data, $where);
    }
}
