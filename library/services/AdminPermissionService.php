<?php
/**
 * 管理员权限管理。
 * @author fingerQin
 * @date 2015-11-19
 */

namespace services;

use models\AdminRole;
use common\YCore;
use finger\Validator;
use models\Menu;
use models\AdminRolePriv;
use models\Admin;
use finger\DbBase;

class AdminPermissionService extends AbstractService
{
    /**
     * 获取角色列表。
     *
     * @return array
     */
    public static function getRoleList()
    {
        $AdminRoleModel = new AdminRole();
        return $AdminRoleModel->getAllRole(false);
    }

    /**
     * 添加角色。
     *
     * @param  string  $rolename     角色名称。
     * @param  int     $listorder    排序。小在前。
     * @param  string  $description  角色介绍。
     * @return void
     */
    public static function addRole($rolename, $listorder = 0, $description = '')
    {
        // [1] 验证
        self::checkRolename($rolename);
        self::checkRoleListorder($listorder);
        self::checkRoleDescription($description);
        $data = [
            'rolename'     => $rolename,
            'listorder'    => $listorder,
            'description'  => $description,
            'is_default'   => AdminRole::STATUS_NO,
            'status'       => AdminRole::STATUS_NORMAL,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $AdminRoleModel = new AdminRole();
        $roleid = $AdminRoleModel->insert($data);
        if (!$roleid) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 编辑角色。
     *
     * @param  int     $roleid        角色ID。
     * @param  string  $rolename      角色名称。
     * @param  int     $listorder     排序。
     * @param  string  $description   角色介绍。
     * @return void
     */
    public static function editRole($roleid, $rolename, $listorder = 0, $description = '')
    {
        // [1] 验证
        self::checkRolename($rolename);
        self::checkRoleListorder($listorder);
        self::checkRoleDescription($description);
        self::isExistRole($roleid);
        $where = [
            'roleid' => $roleid
        ];
        $data = [
            'rolename'    => $rolename,
            'listorder'   => $listorder,
            'description' => $description,
            'status'      => AdminRole::STATUS_NORMAL
        ];
        $AdminRoleModel = new AdminRole();
        $ok = $AdminRoleModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 角色删除。
     *
     * @param  int  $roleid 角色ID。
     * @return void
     */
    public static function deleteRole($roleid)
    {
        $roleInfo = self::isExistRole($roleid);
        if ($roleInfo['is_default'] == Admin::STATUS_YES) {
            YCore::exception(STATUS_ERROR, '默认角色不能删除');
        }
        $AdminModel = new Admin();
        $adminCount = $AdminModel->count([
            'roleid' => $roleid,
            'status' => Admin::STATUS_NORMAL
        ]);
        if ($adminCount == 0) {
            $AdminRoleModel = new AdminRole();
            $where = [
                'roleid' => $roleid,
                'status' => AdminRole::STATUS_NORMAL
            ];
            $updata = [
                'status' => AdminRole::STATUS_DELETED
            ];
            return $AdminRoleModel->update($updata, $where);
        } else {
            YCore::exception(STATUS_ERROR, '请将该角色下的管理员移动到其它角色下');
        }
    }

    /**
     * 角色详情。
     *
     * @param  int  $roleid 角色ID。
     * @return bool
     */
    public static function getRoleDetail($roleid)
    {
        return self::isExistRole($roleid);
    }

    /**
     * 获取指定角色且指定父菜单的子菜单。
     *
     * @param  int   $roleid   角色ID。
     * @param  int   $parentid 父菜单ID。
     * @return array
     */
    public static function getRoleSubMenu($roleid, $parentid)
    {
        if ($roleid == 1) { // 超级管理员验证角色权限。
            return self::getSubMenu($parentid);
        } else {
            $defaultDb = new DbBase();
            $sql = 'SELECT b.* FROM ms_admin_role_priv AS a INNER JOIN ms_menu AS b '
                 . 'ON(a.menu_id=b.menu_id AND a.roleid = :roleid AND b.parentid = :parentid) '
                 . 'WHERE b.display = :display ORDER BY b.listorder ASC,b.menu_id ASC';
            $params = [
                ':parentid' => $parentid,
                ':roleid'   => $roleid,
                ':display'  => Menu::STATUS_YES
            ];
            $list = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
            return $list ? $list : [];
        }
    }

    /**
     * 获取指定ID的子菜单。
     *
     * @param  int   $parentId    父ID。
     * @param  int   $isGetHide   是否获取隐藏的菜单。
     * @return array
     */
    public static function getSubMenu($parentId, $isGetHide = false)
    {
        return self::getByParentToMenu($parentId, $isGetHide);
    }

    /**
     * 获取管理后台左侧菜单。
     *
     * @param  int   $roleid   角色ID。
     * @param  int   $menuId   菜单ID。
     * @return array
     */
    public static function getAdminLeftMenu($roleid, $menuId)
    {
        $menus = self::getRoleSubMenu($roleid, $menuId);
        if (empty($menus)) {
            return [];
        }
        foreach ($menus as $key => $menu) {
            $menus[$key]['sub_menu'] = self::getRoleSubMenu($roleid, $menu['menu_id']);
        }
        return $menus;
    }

    /**
     * 获取菜单列表[tree]。
     *
     * @param  int    $parentid      父ID。默认值0。
     * @param  string $childrenName  子节点键名。
     * @return array
     */
    public static function getMenuList($parentid = 0, $childrenName = 'sub')
    {
        $menus = self::getByParentToMenu($parentid);
        if (empty($menus)) {
            return $menus;
        } else {
            foreach ($menus as $key => $menu) {
                $menus[$key][$childrenName] = self::getMenuList($menu['menu_id']);
            }
            return $menus;
        }
    }

    /**
     * 通过父分类ID读取子菜单。
     *
     * @param  int $parentid  父分类ID。
     * @param  int $isGetHide 是否获取隐藏的菜单。
     * 
     * @return array
     */
    public static function getByParentToMenu($parentid, $isGetHide = true)
    {
        $allMenus = self::getAllMenus();
        $menus    = [];
        foreach ($allMenus as $menu) {
            if (!$isGetHide && $menu['display'] == 0) {
                continue;
            }
            if ($menu['parentid'] == $parentid) {
                $arrKey         = "{$menu['listorder']}_{$menu['menu_id']}";
                $menus[$arrKey] = $menu;
            }
        }
        ksort($menus);
        return $menus;
    }

    /**
     * 获取菜单详情。
     *
     * @param  int   $menuId  菜单ID。
     * @return array
     */
    public static function getMenuDetail($menuId)
    {
        $MenuModel = new Menu();
        return $MenuModel->getMenu($menuId);
    }

    /**
     * 获取菜单面包屑。
     *
     * @param  int     $menuId    菜单ID。
     * @param  string  $crumbs    面包屑。
     * @return string
     */
    public static function getMenuCrumbs($menuId, $crumbs = '')
    {
        $menu = self::getMenuDetail($menuId);
        if ($menu && $menu['parentid'] > 0) {
            $crumbs = " {$menu['name']} > {$crumbs}";
            return self::getMenuCrumbs($menu['parentid'], $crumbs);
        } else {
            return "{$menu['name']} > {$crumbs}";
        }
    }

    /**
     * 添加菜单。
     *
     * @param  int      $parentid          父菜单ID。
     * @param  string   $name              菜单名称。
     * @param  string   $controllerName    控制器名称。
     * @param  string   $actionName        操作名称。
     * @param  string   $data              附加参数。
     * @param  int      $listorder         排序。
     * @param  int      $display           是否显示。
     * @return void
     */
    public static function addMenu($parentid, $name, $controllerName, $actionName, $data, $listorder, $display = 0)
    {
        self::checkMenuName($name);
        self::checkMenuControllerName($parentid, $controllerName);
        self::checkMenuActionName($parentid, $actionName);
        self::checkMenuAdditionData($data);
        $listorder = intval($listorder);
        $display   = intval($display);
        $parentid  = intval($parentid);
        $data = [
            'name'      => $name,
            'parentid'  => $parentid,
            'c'         => $controllerName,
            'a'         => $actionName,
            'data'      => $data,
            'listorder' => $listorder,
            'display'   => $display
        ];
        $MenuModel = new Menu();
        $ok = $MenuModel->insert($data);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 编辑菜单。
     *
     * @param  int       $menuId             菜单ID。
     * @param  int       $parentid           父菜单ID。
     * @param  string    $name               菜单名称。
     * @param  string    $controllerName     控制器名称。
     * @param  string    $actionName         操作名称。
     * @param  string    $data               附加参数。
     * @param  int       $listorder          排序。
     * @param  int       $display            是否显示。
     * @return void
     */
    public static function editMenu($menuId, $parentid, $name, $controllerName, $actionName, $data, $listorder, $display = 0)
    {
        self::checkMenuName($name);
        self::checkMenuControllerName($parentid, $controllerName);
        self::checkMenuActionName($parentid, $actionName);
        self::checkMenuAdditionData($data);
        $listorder = intval($listorder);
        $display   = intval($display);
        $parentid  = intval($parentid);
        self::isExistMenu($menuId);
        $data = [
            'name'      => $name,
            'parentid'  => $parentid,
            'c'         => $controllerName,
            'a'         => $actionName,
            'data'      => $data,
            'listorder' => $listorder,
            'display'   => $display
        ];
        $MenuModel = new Menu();
        $where = [
            'menu_id' => $menuId
        ];
        $ok = $MenuModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 删除菜单。
     *
     * @param  int  $menuId 菜单ID。
     * @return void
     */
    public static function deleteMenu($menuId)
    {
        self::isExistMenu($menuId);
        $MenuModel = new Menu();
        $subMenu   = $MenuModel->fetchAll([], [
            'parentid' => $menuId
        ]);
        if ($subMenu) {
            YCore::exception(STATUS_ERROR, '请先移除该菜单下的子菜单再删除');
        }
        $where = [
            'menu_id' => $menuId
        ];
        $ok = $MenuModel->delete($where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 菜单排序。
     *
     * @param  array $listorders 菜单排序数据。[ ['菜单ID' => '排序值'], ...... ]
     * @return void
     */
    public static function sortMenu($listorders)
    {
        if (empty($listorders)) {
            return;
        }
        foreach ($listorders as $menuId => $sortVal) {
            $MenuModel = new Menu();
            $ok = $MenuModel->sortMenu($menuId, $sortVal);
            if (! $ok) {
                return YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
            }
        }
    }

    /**
     * 设置角色权限。
     *
     * @param  int   $roleid   角色ID。
     * @param  array $menus    菜单ID数组。
     * @return void
     */
    public static function setRolePermission($roleid, $menus)
    {
        // [1] 角色判断。
        self::isExistRole($roleid);
        // [2] 清空角色之前的数据。
        $AdminRolePrivModel = new AdminRolePriv();
        $AdminRolePrivModel->clearRolePriv($roleid);
        // [3] 添加权限到角色。
        foreach ($menus as $menuId) {
            self::isExistMenu($menuId);
            $ok = $AdminRolePrivModel->addRolePriv($roleid, $menuId);
            if (! $ok) {
                YCore::exception(STATUS_ERROR, '权限添加失败，请重试');
            }
        }
    }

    /**
     * 获取角色对应的权限菜单(树形结构)。
     *
     * @param  int   $roleid 角色ID。
     * @return array
     */
    public static function getRolePermissionMenu($roleid)
    {
        self::isExistRole($roleid);
        $AdminRolePrivModel = new AdminRolePriv();
        $list               = $AdminRolePrivModel->fetchAll([], ['roleid' => $roleid]);
        $privMenuList       = []; // 只存在菜单ID。
        foreach ($list as $menu) {
            $privMenuList[] = $menu['menu_id'];
        }
        return $privMenuList;
    }

    /**
     * 检查角色是否拥有当前链接权限。
     *
     * @param  int     $roleid    角色ID。
     * @param  string  $m         模块名称。
     * @param  string  $c         控制器名称。
     * @param  string  $a         操作名称。
     * @return bool
     */
    public static function checkRoleMenuPriv($roleid, $c, $a)
    {
        $rolePermissionList = self::getRolePermission($roleid);
        $isOk = false;
        foreach ($rolePermissionList as $per) {
            if ($per['c'] == $c && $per['a'] == $a) {
                $isOk = true;
                break;
            }
        }
        return $isOk;
    }

    /**
     * 检查角色名称格式。
     * 
     * @param  string $rolename 角色名称。
     * @return void
     */
    public static function checkRolename($rolename)
    {
        // [1] 验证
        $data = [
            'rolename' => $rolename
        ];
        $rules = [
            'rolename' => '角色|require|len:2:10:1'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查角色介绍是否正确。
     * @param  string $description 角色介绍。
     * @return void
     */
    public static function checkRoleDescription($description)
    {
        // [1] 验证
        $data = [
            'description' => $description
        ];
        $rules = [
            'description' => '角色介绍|require|len:1:100:1',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查角色排序值格式。
     * 
     * @param  int  $listorder 排序值。
     * @return void
     */
    public static function checkRoleListorder($listorder)
    {
        // [1] 验证
        $data = [
            'listorder' => $listorder
        ];
        $rules = [
            'listorder' => '排序|require|integer',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查角色格式。
     * 
     * @param  int     $roleid  角色ID。
     * @param  string  $errMsg  自定义错误提示。
     * @return array
     */
    public static function isExistRole($roleid, $errMsg = '')
    {
        $admin_role_model = new AdminRole();
        $role_info        = $admin_role_model->fetchOne([], [
            'roleid' => $roleid,
            'status' => AdminRole::STATUS_NORMAL
        ]);
        if (empty($role_info)) {
            YCore::exception(STATUS_ERROR, '角色不存在或已经删除');
        }
        return $role_info;
    }

    /**
     * 检查资源菜单名称。
     * 
     * @param  string $name 菜单名称。
     * @return void
     */
    public static function checkMenuName($name)
    {
        $data = [
            'name' => $name
        ];
        $rules = [
            'name' => '菜单名称|require|len:2:10:1',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查资源菜单控制器名称。
     * 
     * @param  int    $parentid       父级菜单ID。
     * @param  string $controllerName 菜单控制器名称。
     * @return void
     */
    public static function checkMenuControllerName($parentid, $controllerName)
    {
        // 一级菜单不需要填写。
        if ($parentid == 0) {
            return;
        }
        $data = [
            'name' => $controllerName
        ];
        $rules = [
            'name' => '控制器名称|require',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查资源菜单操作名称。
     * 
     * @param  int    $parentid   父级菜单ID。
     * @param  string $actionName 菜单操作名称。
     * @return void
     */
    public static function checkMenuActionName($parentid, $actionName)
    {
        // 一级菜单不需要填写。
        if ($parentid == 0) {
            return;
        }
        $data = [
            'name' => $actionName
        ];
        $rules = [
            'name' => '操作名称|require',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 检查资源菜单附加参数。
     * 
     * @param  string $additionData 菜单操作名称。
     * @return void
     */
    public static function checkMenuAdditionData($additionData)
    {
        $data = [
            'data' => $additionData
        ];
        $rules = [
            'data' => '附加参数|len:0:100:1',
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
    }

    /**
     * 菜单是否存在。
     * 
     * @param  int     $menuId  菜单ID。
     * @param  string  $errMsg  自定义错误信息。
     * 
     * @return array
     */
    public static function isExistMenu($menuId, $errMsg = '')
    {
        $MenuModel = new Menu();
        $menuInfo  = $MenuModel->fetchOne([], ['menu_id' => $menuId]);
        if (empty($menuInfo)) {
            YCore::exception(STATUS_ERROR, '菜单不存在或已经删除');
        }
        return $menuInfo;
    }

    /**
     * 获取全部菜单。
     *
     * @return array
     */
    protected static function getAllMenus()
    {
        $cacheKey = 'phpjieshuo_get_all_menus';
        if (\Yaf_Registry::has($cacheKey)) {
            return \Yaf_Registry::get($cacheKey);
        } else {
            $where     = [];
            $columns   = [];
            $MenuModel = new Menu();
            $result    = $MenuModel->fetchAll($columns, $where);
            \Yaf_Registry::set($cacheKey, $result);
            return $result;
        }
    }
}
