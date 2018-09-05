<?php
/**
 * 用户收藏表。
 * @author fingerQin
 * @date 2016-08-22
 */

namespace models;

class Favorites extends AbstractBase
{

    const OBJ_TYPE_NEWS     = 1;    // 文章。
    const OBJ_TYPE_LINK     = 2;    // 友情链接。
    const OBJ_TYPE_GOODS    = 3;    // 商品。
    const OBJ_TYPE_QUESTION = 4;    // 题目。
    const OBJ_TYPE_DOC      = 5;    // 技术文档。

    /**
     * 表名。
     *
     * @var string
     */
    protected $tableName = 'ms_favorites';

}