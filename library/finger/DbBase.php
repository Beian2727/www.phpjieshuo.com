<?php
/**
 * DbBase 基类。
 * @author fingerQin
 * @date 2017-09-02
 */

namespace finger;

use common\YCore;
use common\YLog;

class DbBase 
{
    /**
     * 数据库连接资源句柄。
     *
     * @var \PDO
     */
    protected $dbClient = null;

    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * 分表数量。
     * -- 当大于0的时候,说明当前表是分表应用。数值代表分表的数量。
     * -- 分表的情况下,具体的表名为：表名_数字。如：log_1。
     *
     * @var int
     */
    protected $splitTableCount = 0;

    /**
     * 在使用预处理语句时使用。
     * -- 即建立一个只能向后的指针。
     *
     * @var array
     */
    protected $prepareAttr = [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY];

    /**
     * 连接哪个数据库配置。对应系统配置文件 application.ini 当中 database.mysql.xxx.host 的 xxx
     *
     * @var string
     */
    protected $dbOption  = 'default';

    /**
     *
     * @var 保存最后操作的PDOStatement对象。
     */
    protected $stmt      = null;

    /**
     * 表更新时间。
     * 
     * @var string
     */
    protected $createTime = 'created_time';

    /**
     * 更新时间字段。
     * 
     * @var string
     */
    protected $updateTime = 'modified_time';

    /**
     * 构造方法。
     *
     * @param  string  $dbOption 数据库配置项。
     * @return void
     */
    public function __construct() {
        $this->changeDb($this->dbOption);
    }

    /**
     * 切换数据库连接。
     *
     * @param  string  $dbOption 数据库配置项。
     * @return void
     */
    public function changeDb($dbOption)
    {
        $registryName = "mysql_{$dbOption}";
        if (\Yaf_Registry::has($registryName) === false) {
            $this->connection($dbOption);
        }
        $this->dbClient = \Yaf_Registry::get($registryName);
    }

    /**
     * 返回真实的数据库对象。
     * @return PDO
     */
    public function getDbClient()
    {
        return $this->dbClient;
    }

    /**
     * 连接数据库。
     *
     * @param  string  $dbOption 数据库配置项。
     * @return void
     */
    protected function connection($dbOption = 'default')
    {
        $registryName  = "mysql_{$dbOption}";
        // [1] 传统初始化MySQL方式。
        $config   = \Yaf_Registry::get("config");
        $host     = $config->database->mysql->$dbOption->host;
        $port     = $config->database->mysql->$dbOption->port;
        $username = $config->database->mysql->$dbOption->username;
        $password = $config->database->mysql->$dbOption->password;
        $charset  = $config->database->mysql->$dbOption->charset;
        $dbname   = $config->database->mysql->$dbOption->dbname;
        $dsn      = "mysql:dbname={$dbname};host={$host};port={$port}";
        $dbh      = new \PDO($dsn, $username, $password);
        // MySQL操作出错，抛出异常。
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // $dbh->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $dbh->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);
        $dbh->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, FALSE);
        $dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, FALSE);
        // 以关联数组返回查询结果。
        $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $dbh->query("SET NAMES {$charset}");
        \Yaf_Registry::set($registryName, $dbh);
    }

    /**
     * 数据库重连。
     *
     * @param  string  $dbOption  数据库配置项。断线重连时，以哪个数据库配置重连。
     * 
     * @return void
     */
    final public function reconnect($dbOption = 'default')
    {
        $registryName = "mysql_{$dbOption}";
        $this->connection($dbOption);
        $this->dbClient = \Yaf_Registry::get($registryName);
    }

    /**
     * 获取表名。
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * 开启数据库事务。
     */
    final public function beginTransaction()
    {
        $is_active = $this->dbClient->inTransaction();
        if (!$is_active) {
            $bool  = $this->dbClient->beginTransaction();
            if (!$bool) {
                $this->openTransactionFailed();
            }
        }
    }

    /**
     * 检查连接是否可用(类似于http ping)。
     * 
     * -- 向 MySQL 服务器发送获取服务器信息的请求。
     * 
     * @param  int     $isReconnect     当与 MySQL 服务器的连接不可用时,是否重连。默认断线重连。
     * @param  string  $dbOption        数据库配置项。断线重连时，以哪个数据库配置重连。
     * 
     * @return bool
     */
    final public function ping($isReconnect = true, $dbOption = 'default')
    {
        if (!$this->dbClient) {
            YCore::exception(-1, '请正确连接数据库');
        }
        try {
            $info = $this->dbClient->getAttribute(\PDO::ATTR_SERVER_INFO);
            if (is_null($info)) {
                $isReconnect && $this->reconnect($dbOption);
                return false;
            } else {
                return true;
            }
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                $isReconnect && $this->reconnect($dbOption);
                return false;
            } else {
                YCore::exception(-1, "MySQL服务器返回异常:" . $e->getMessage());
            }
        }
    }

    /**
     * 提交数据库事务。
     */
    final public function commit()
    {
        $is_active = $this->dbClient->inTransaction();
        if ($is_active) {
            $bool  = $this->dbClient->commit();
            if (!$bool) {
                $this->commitTransactionFailed();
            }
        }
    }

    /**
     * 回滚数据库事务。
     */
    final public function rollBack()
    {
        $is_active = $this->dbClient->inTransaction();
        if ($is_active) {
            $bool  = $this->dbClient->rollBack();
            if (!$bool) {
                $this->rollbackTransactionFailed();
            }
        }
    }

    /**
     * 获取最后插入的ID。
     *
     * @return number
     */
    public function lastInsertId()
    {
        return $this->dbClient->lastInsertId();
    }

    /**
     * 执行sql查询
     *
     * @param  array   $columns  需要查询的字段值[例`name`,`gender`,`birthday`]
     * @param  array   $where    查询条件[例`name`='$name']
     * @param  int     $limit    返回的结果条数。
     * @param  string  $orderBy  排序方式 [默认按数据库默认方式排序]
     * @param  string  $groupBy  分组方式 [默认为空]
     * @return array 查询结果集数组
     */
    public function fetchAll(array $columns = [], array $where = [], $limit = 0, $orderBy = '', $groupBy = '')
    {
        // [1] 参数判断。
        $this->checkTableName();
        $this->checkOrderBy($orderBy);
        $this->checkGroupBy($groupBy);
        $this->checkLimit($limit);

        // [2] where 条件生成。
        $whereCondition = ' 1 AND 1 ';
        $params         = [];
        if (!empty($where)) {
            $whereInfo       = $this->parseWhereCondition($where);
            $whereCondition .= " AND {$whereInfo['where']} ";
            $params          = array_merge($params, $whereInfo['params']);
        }

        // [3] 要查询的列名。
        $columnCondition = '';
        if (empty($columns)) {
            $columnCondition = ' * ';
        } else {
            foreach ($columns as $columnName) {
                $columnCondition .= "`{$columnName}`,";
            }
        }
        $columnCondition = trim($columnCondition, ',');

        // [4] GROUP BY 处理。
        if (strlen($groupBy) > 0) {
            $groupBy = "GROUP BY {$groupBy}";
        }

        // [5] ORDER BY 处理。
        if (strlen($orderBy) > 0) {
            $orderBy = "ORDER BY {$orderBy}";
        }
        if ($limit == 0) {
            $sql = "SELECT {$columnCondition} FROM `{$this->tableName}` " 
                 . "WHERE {$whereCondition} {$groupBy} {$orderBy}";
        } else {
            $sql = "SELECT {$columnCondition} FROM `{$this->tableName}` " 
                 . "WHERE {$whereCondition} {$groupBy} {$orderBy} LIMIT {$limit}";
        }
        $this->writeSqlLog($sql, $params);
        $sth  = $this->dbClient->prepare($sql);
        $sth->execute($params);
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $data ? $data : [];
    }

    /**
     * 获取单条记录查询
     *
     * @param  array   $columns   需要查询的字段值。['username', 'sex', 'mobilephone']
     * @param  array   $where     查询条件
     * @param  string  $orderBy   排序方式 [默认按数据库默认方式排序]
     * @param  string  $groupBy   分组方式 [默认为空]
     * 
     * @return array 数据查询结果集,如果不存在，则返回空数组。
     */
    public function fetchOne(array $columns, array $where, $orderBy = '', $groupBy = '')
    {
        // [1] 参数判断。
        $this->checkTableName();
        $this->checkWhere($where);
        $this->checkOrderBy($orderBy);
        $this->checkGroupBy($groupBy);

        // [2] where 条件生成。
        $whereCondition = ' 1 AND 1 ';
        $params         = [];
        if (!empty($where)) {
            $whereInfo       = $this->parseWhereCondition($where);
            $whereCondition .= " AND {$whereInfo['where']} ";
            $params          = array_merge($params, $whereInfo['params']);
        }

        // [3] 要查询的列名。
        $columnCondition = '';
        if (empty($columns)) {
            $columnCondition = ' * ';
        } else {
            foreach ($columns as $columnName) {
                $columnCondition .= "`{$columnName}`,";
            }
        }
        $columnCondition = trim($columnCondition, ',');

        // [4] GROUP BY 处理。
        if (strlen($groupBy) > 0) {
            $groupBy = "GROUP BY {$groupBy}";
        }

        // [5] ORDER BY 处理。
        if (strlen($orderBy) > 0) {
            $orderBy = "ORDER BY {$orderBy}";
        }
        $sql  = "SELECT {$columnCondition} FROM `{$this->tableName}` " 
              . "WHERE {$whereCondition} {$groupBy} {$orderBy} LIMIT 1";
        $this->writeSqlLog($sql, $params);
        $sth  = $this->dbClient->prepare($sql);
        $sth->execute($params);
        $data = $sth->fetch(\PDO::FETCH_ASSOC);
        return $data ? $data : [];
    }

    /**
     * 获取记录条数。
     *
     * @param  array  $where 查询条件
     * @return int
     */
    public function count(array $where)
    {
        // [1] 参数判断。
        $this->checkTableName() ;
        // [2] where 条件生成。
        $whereCondition = ' 1 ';
        $params         = [];
        if (!empty($where)) {
            $whereInfo       = $this->parseWhereCondition($where);
            $whereCondition .= " AND {$whereInfo['where']} ";
            $params          = array_merge($params, $whereInfo['params']);
        }
        $whereInfo      = $this->parseWhereCondition($where);
        $params         = $whereInfo['params'];
        $whereCondition = $whereInfo['where'];
        // [3] 要查询的列名。
        $columnCondition = 'COUNT(1) AS count';
        $sql  = "SELECT {$columnCondition} FROM `{$this->tableName}` WHERE {$whereCondition} LIMIT 1";
        $sth  = $this->dbClient->prepare($sql);
        $this->writeSqlLog($sql, $params);
        $sth->execute($params);
        $data = $sth->fetch(\PDO::FETCH_ASSOC);
        return $data ? intval($data['count']) : 0;
    }

    /**
     * 执行添加记录操作
     *
     * @param  array  $data 要增加的数据，参数为数组。数组key为字段值，数组值为数据取值
     * @return int 大于0为主键id，等于0为添加失败。
     */
    public function insert(array $data)
    {
        $this->checkTableName();
        if (empty($data)) {
            YCore::exception(-1, "Insert the data parameter can't be empty", false);
        }
        $this->checkInsertTime($data);
        $columnCondition = '';
        $columnQuestion  = '';
        $params          = [];
        foreach ($data as $column_name => $column_val) {
            $columnCondition .= "`{$column_name}`,";
            $columnQuestion  .= "?,";
            $params[]         = $column_val;
        }
        $columnCondition = trim($columnCondition, ',');
        $columnQuestion  = trim($columnQuestion, ',');
        $sql             = "INSERT INTO `{$this->tableName}` ($columnCondition) VALUES($columnQuestion) ";
        $this->writeSqlLog($sql, $params);
        $sth             = $this->dbClient->prepare($sql);
        $ok              = $sth->execute($params);
        unset($columnCondition, $columnQuestion, $params);
        return $ok ? $this->dbClient->lastInsertId() : 0;
    }

    /**
     * 检查插入的数组里面的更新/创建时间。
     * 
     * -- 当未设置的时候自动添加。
     *
     * @param  array  &$data  待插入的数据。
     * @return void
     */
    protected function checkInsertTime(&$data)
    {
        $datetime = date('Y-m-d H:i:s', time());
        if (!isset($data[$this->createTime])) {
            $data[$this->createTime] = $datetime;
        }
        if (!isset($data[$this->updateTime])) {
            $data[$this->updateTime] = $datetime;
        }
    }

    /**
     * 执行更新记录操作。
     *
     * @param  array  $data  要更新的数据内容。
     * @param  array  $where 更新数据时的条件。必须有条件。避免整表更新。
     * @return bool
     */
    public function update(array $data, array $where)
    {
        // [1] 参数判断。
        $this->checkTableName();
        $this->checkWhere($where);
        if (empty($data)) {
            YCore::exception(-1, 'Update the data parameter can\'t be empty');
        }
        $this->checkInsertTime($data);
        // [2] SET 条件生成。
        $setCondition = '';
        $params       = [];
        foreach ($data as $columnName => $columnVal) {
            $setCondition .= "`{$columnName}` = :__c_{$columnName},";
            $params[":__c_{$columnName}"] = $columnVal;
        }
        $setCondition = trim($setCondition, ',');
        // [3] where 条件生成。
        $whereInfo      = $this->parseWhereCondition($where);
        $whereCondition = $whereInfo['where'];
        $params         = array_merge($params, $whereInfo['params']);
        $sql            = "UPDATE `{$this->tableName}` SET {$setCondition} WHERE {$whereCondition} ";
        $this->writeSqlLog($sql, $params);
        $sth            = $this->dbClient->prepare($sql);
        $ok             = $sth->execute($params);
        unset($params, $setCondition, $whereCondition);
        if ($ok) {
            $affectedRow = $sth->rowCount();
            return $affectedRow > 0 ? true : false;
        } else {
            return false;
        }
    }

    /**
     * 执行删除记录操作。
     *
     * @param  array  $where 删除数据条件,不充许为空。
     * @return bool
     */
    public function delete(array $where)
    {
        $this->checkTableName();
        $this->checkWhere($where);
        $sql        = "DELETE FROM `{$this->tableName}` WHERE 1 = 1 AND ";
        $whereInfo  = $this->parseWhereCondition($where);
        $sql       .= $whereInfo['where'];
        $sth        = $this->dbClient->prepare($sql);
        $this->writeSqlLog($sql, $whereInfo['params']);
        $sth->execute($whereInfo['params']);
        $affectedRow = $sth->rowCount();
        return $affectedRow > 0 ? true : false;
    }

    /**
     * 解析 where 条件。
     * -- Example start --
     * # 示例1：
     * $where = [
     *      'username'    => 'fingerQin',
     *      'mobilephone' => '13xxxxxxxxx',
     * ];
     * # 转换后:
     * AND username = :username AND mobilephone = :mobilephone
     *
     * # 示例2：
     * $where = [
     *      'age'   => ['>', '6'],
     *      'sex'   => ['!=', 1],
     *      'sex'   => ['<>', 1],
     *      'money' => ['<', '100'],
     *      'user'  => ['LIKE', '%finger%'],
     * ];
     * # 转换后：
     * AND age > :age AND money < :money AND user LIKE :user
     *
     * # 示例3：
     * $where = [
     *      'order_status' => ['IN', [1, 2, 3]],
     *      'status'       => ['NOT IN', [1, 2]],
     *      'time'         => ['BETWEEN', ['2017-09-06 12:00:00', '2017-10-01 12:00:00]]
     * ];
     * AND order_status IN (:order_status1, :order_status2, :order_status3) 
     * AND status NOT IN (:status1, :status2)
     * AND time BETWEEN :time1 AND :time2
     *
     * -- Example end --
     *
     * @return array $arrWhere where 条件。
     * @return array
     * -- return result start --
     * [
     *      'where'  => 'username = :username AND mobilephone = :mobilephone',
     *      'params' => [
     *          ':username'    => 'fingerQin', 
     *          ':mobilephone' => '13xxxxxxxxx'
     *      ],
     * ];
     * -- return result end --
     */
    public function parseWhereCondition($arrWhere)
    {
        $where  = '';
        $params = [];
        if (empty($arrWhere)) {
            return [
                'where'  => $where,
                'params' => $params
            ];
        }
        foreach ($arrWhere as $field => $item) {
            if (!is_string($field)) {
                YCore::exception(-1, "The keys of the where clause for corresponding values ({$field}) is not a string type");
            }
            if (is_string($item) || is_numeric($item)) {
                $where .= " AND `{$field}` = :{$field} ";
                $params[":{$field}"] = $item;
            } else if (is_array($item)) {
                if (empty($item)) {
                    YCore::exception(-1, "The keys of the where clause for corresponding values ({$field}) is not a array type");
                }
                if (!isset($item[0])) {
                    YCore::exception(-1, "The field {$field} is not set conditions for operation symbols");
                }
                if (!is_string($item[0]) && !is_numeric($item[0])) {
                    YCore::exception(-1, "The field {$field} must be a string type");
                }
                $ops = trim(strtolower($item[0]));
                switch ($ops) {
                    case '>'    :
                    case '<'    :
                    case '<='   :
                    case '>='   :
                    case '='    :
                    case '!='   :
                    case '<>'   :
                    case 'like' :
                        if (!isset($item[1])) {
                            YCore::exception(-1, "The field {$field} is not set conditions for value");
                        }
                        if (!is_string($item[1]) && !is_numeric($item[1])) {
                            YCore::exception(-1, "The field {$field} must be a string type");
                        }
                        $where .= " AND `{$field}` {$ops} :{$field} ";
                        $params[":{$field}"] = $item[1];
                        break;
                    case 'in' :
                    case 'not in' :
                        if (!isset($item[1])) {
                            YCore::exception(-1, "The field {$field} is not set conditions for value");
                        }
                        if (!is_array($item[1])) {
                            YCore::exception(-1, "The field {$field} must be a array type");
                        }
                        if (empty($item[1])) {
                            continue;
                        }
                        $_where = '';
                        foreach ($item[1] as $k => $v) {
                            $_where .= " :{$field}_{$k}, ";
                            $params[":{$field}_{$k}"] = $v;
                        }
                        $_where = trim($_where, ', ');
                        $where .= " AND `{$field}` {$ops} ({$_where}) ";
                        break;
                    case 'between':
                        if (!isset($item[1])) {
                            YCore::exception(-1, "The field {$field} is not set conditions for value");
                        }
                        if (!is_array($item[1])) {
                            YCore::exception(-1, "The field {$field} must be a array type");
                        }
                        if (empty($item[1])) {
                            YCore::exception(-1, "This field's({$field}) between scope values must be set");
                        }
                        if (!isset($item[1][0])) {
                            YCore::exception(-1, "The between left value of this field({$field}) must be set");
                        }
                        if (!isset($item[1][1])) {
                            YCore::exception(-1, "The between right value of this field({$field}) must be set");
                        }
                        $where .= " AND `{$field}` BETWEEN :{$field}_0 AND :{$field}_1 ";
                        $params[":{$field}_0"] = $item[1][0];
                        $params[":{$field}_1"] = $item[1][1];
                        break;
                    default :
                        YCore::exception(-1, "{$ops} operator does not exist");
                        break;
                }
            }
        }
        $where = trim($where, ' AND');
        return [
            'where'  => $where,
            'params' => $params
        ];
    }

    /**
     * 计算并返回每页的offset.
     *
     * @param  int  $page   页码。
     * @param  int  $count  每页显示记录条数。
     * @return int
     */
    public function getPaginationOffset($page, $count){

        $count = ($count <= 0) ? 10 : $count;
        $page  = ($page <= 0) ? 1 : $page;
        return ($page == 1) ? 0 : (($page -1) * $count);
    }

    /**
     * 计算是否有下一页。
     *
     * @param  int  $total 总条数。
     * @param  int  $page 当前页。
     * @param  int  $count 每页显示多少条。
     * @return bool
     */
    public function isHasNextPage($total, $page, $count)
    {
        if (!$total || !$count) {
            return false;
        }
        $total_page = ceil($total / $count);
        if (!$total_page) {
            return false;
        }
        if ($total_page <= $page) {
            return false;
        }
        return true;
    }

    /**
     * 原生SQL查询。
     *
     * @param  string  $sql 查询SQL。
     * @param  array   $params 绑定参数。
     * @return \models\Base
     */
    public function rawQuery($sql, $params = [])
    {
        $this->writeSqlLog($sql, $params);
        $this->stmt = $this->dbClient->prepare($sql);
        $this->stmt->execute($params);
        return $this;
    }

    /**
     * 更新、删除、添加。
     *
     * @param  string   $sql 查询SQL。
     * @param  array    $params 绑定参数。
     * @return bool|int
     */
    public function rawExec($sql, $params = [])
    {
        $this->writeSqlLog($sql, $params);
        $sth = $this->dbClient->prepare($sql);
        $sth->execute($params);
        $sql_type = strtolower(substr($sql, 0, 6));
        $is_insert_sql = ($sql_type == 'insert') ? true : false;
        if ($is_insert_sql) {
            return $this->lastInsertId();
        } else {
            $affected_row = $sth->rowCount();
            return $affected_row > 0 ? true : false;
        }
    }

    /**
     * 获取单行结果。
     *
     * @return array
     */
    public function rawFetchOne()
    {
        $this->checkStatement();
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : [];
    }

    /**
     * 获取全部结果。
     *
     * @return array
     */
    public function rawFetchAll()
    {
        $this->checkStatement();
        $result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result ? $result : [];
    }

    /**
     * 检查表名是否合法。
     * @return bool
     */
    protected function checkTableName()
    {
        if (!is_string($this->tableName) || strlen($this->tableName) === 0) {
            YCore::exception(-1, 'The tableName parameters is wrong');
        }
        return true;
    }

    /**
     * 检查排序条件是否合法。
     * @param  string $orderBy 排序条件。
     * @return bool
     */
    protected function checkOrderBy($orderBy)
    {
        if (!is_string($orderBy)) {
            YCore::exception(-1, 'The orderBy parameters is wrong');
        }
        return true;
    }

    /**
     * 检查查询条件是否合法。
     * @param  array  $where 查询条件。
     * @return bool
     */
    protected function checkWhere(array $where)
    {
        if (empty($where)) {
            YCore::exception(-1, 'The where parameters is wrong');
        }
        return true;
    }

    /**
     * 检查 limit 参数是否合法。
     * @param  integer $limit limit 参数。
     * @return bool
     */
    protected function checkLimit($limit)
    {
        if (!is_numeric($limit)) {
            YCore::exception(-1, 'The limit parameter is wrong');
        }
        return true;
    }

    /**
     * 检查分组参数是否合法。
     * @param  string $groupBy 分组参数。
     * @return bool
     */
    protected function checkGroupBy($groupBy)
    {
        if (!is_string($groupBy)) {
            YCore::exception(-1, 'The groupBy parameter is wrong');
        }
        return true;
    }

    /**
     * 检查 statement 是否有效。
     * @return bool
     */
    protected function checkStatement()
    {
        if (empty($this->stmt)) {
            YCore::exception(-1, 'The PDO statement not instantiate, please determine whether to perform the rawQuery or rawExec');
        }
        return true;
    }

    /**
     * 事务开启失败。
     * @return void
     */
    protected function openTransactionFailed()
    {
        YCore::exception(-1, 'Open transaction failure');
    }

    /**
     * 提交事务失败。
     * @return void
     */
    protected function commitTransactionFailed()
    {
        YCore::exception(-1, 'Transaction commit failure');
    }

    /**
     * 提交事务失败。
     * @return void
     */
    protected function rollbackTransactionFailed()
    {
        YCore::exception(-1, 'Transaction rollback failed');
    }

    /**
     * 记录 SQL 日志。
     * 
     * -- 正式环境不记录执行的 SQL
     *
     * @param  string  $sql     执行的 SQL。
     * @param  array   $params  SQL 参数。
     * @return void
     */
    protected function writeSqlLog($sql, $params = [])
    {
        $env = YCore::appconfig('env.name');
        if ($env != 'prod') {
            foreach ($params as $key => $val) {
                $val = "'" . addslashes($val) . "'";
                $sql = str_replace($key, $val, $sql);
            }
            YLog::save($sql, 'sql', 'log');
        }
    }
}