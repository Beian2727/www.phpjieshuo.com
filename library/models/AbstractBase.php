<?php
/**
 * Model 基类。
 * 
 * -- 本来想取类名为 AbstractModel,但是，名称中带 Model, Yaf 框架会自动去掉。
 * 
 * @author fingerQin
 * @date 2018-05-18
 */

namespace models;

abstract class AbstractBase extends \finger\DbBase
{
    const STATUS_INVALID = 0;   // 记录状态：无效。
    const STATUS_NORMAL  = 1;   // 记录状态：正常。
    const STATUS_DELETED = 2;   // 记录状态：已删除。

    const STATUS_YES     = 1;   // 是。
    const STATUS_NO      = 0;   // 否。

    const NONE           = -1;  // 所有地方传-1都代表此值未传。

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
}