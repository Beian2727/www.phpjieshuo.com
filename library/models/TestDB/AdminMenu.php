<?php
/**
 * 多库表测试。
 * @author fingerQin
 * @date 2015-11-17
 */

namespace models\TestDB;

class AdminMenu extends \models\AbstractModel
{
    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'tb_admin_menu';

    /**
     * 连接哪个数据库配置。
     *
     * @var string
     */
    protected $dbOption  = 'test';
}
