<?php
/**
 * 收藏夹模块封装。
 * @author fingerQin
 * @date 2016-08-24
 */

namespace services;

use finger\DbBase;
use common\YCore;
use common\YUrl;
use models\Favorites;
use models\News;
use models\GmQuestion;

class FavoritesService extends AbstractService
{
    /**
     * 获取收藏列表。
     *
     * @param  int   $userId   用户ID。
     * @param  int   $objType  收藏类型：1商品收藏、2文章收藏、3友情链接、4题库、5培训资料文章。
     * @param  int   $page     当前页码。
     * @param  int   $count    每页显示条数。
     * @return array
     */
    public static function getList($userId, $objType, $page, $count)
    {
        $offset  = self::getPaginationOffset($page, $count);
        $columns = ' * ';
        $where   = ' WHERE user_id = :user_id AND obj_type = :obj_type AND status = :status';
        $params  = [
            ':user_id'  => $userId,
            ':obj_type' => $objType,
            ':status'   => Favorites::STATUS_NORMAL
        ];
        $orderBy   = ' ORDER BY id DESC ';
        $sql       = "SELECT COUNT(1) AS count FROM ms_favorites {$where}";
        $defaultDb = new DbBase();
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM ms_favorites {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        foreach ($list as $k => $v) {
            $v['created_time'] = YCore::formatTimestamp($v['created_time']);
            switch ($v['obj_type']) {
                case 1: // 技术文章。
                    $sql            = 'SELECT news_id AS id,title,image_url,status FROM ms_news WHERE news_id = :news_id LIMIT 1';
                    $newsDetail     = $defaultDb->rawQuery($sql, [':news_id' => $v['obj_id'], 'status' => News::STATUS_NORMAL])->rawFetchOne();
                    $v['id']        = $newsDetail['id'];
                    $v['title']     = $newsDetail['title'];
                    $v['image_url'] = YUrl::filePath($newsDetail['image_url']);
                    break;
                case 2: // 培训题目。
                    $sql            = 'SELECT question_id AS id,title,image_url,status FROM gm_question WHERE news_id = :news_id LIMIT 1';
                    $questionDetail = $defaultDb->rawQuery($sql, [':news_id' => $v['obj_id'], 'status' => GmQuestion::STATUS_NORMAL])->rawFetchOne();
                    $v['id']        = $questionDetail['id'];
                    $v['title']     = $questionDetail['title'];
                    $v['image_url'] = YUrl::filePath($questionDetail['image_url']);
                    break;
            }
            $list[$k] = $v;
        }
        $result = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => self::IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 添加收藏。
     *
     * @param  int   $userId  用户ID。
     * @param  int   $objType 收藏类型：1商品收藏、2文章收藏、3友情链接、4题库、5培训资料文章。
     * @param  int   $objId   商品ID/文章ID。
     * @return bool
     */
    public static function add($userId, $objType, $objId)
    {
        $defaultDb = new DbBase();
        switch ($objType) {
            case 1: // 文章。
                $sql        = 'SELECT * FROM ms_news WHERE news_id = :news_id AND status = :status LIMIT 1';
                $newsDetail = $defaultDb->rawQuery($sql, [':news_id' => $objId, ':status' => News::STATUS_NORMAL])->rawFetchOne();
                if (empty($newsDetail)) {
                    YCore::exception(STATUS_ERROR, '该记录已经删除');
                }
                break;
            case 2: // 培训题目。
                $sql            = 'SELECT question_id AS id,title,image_url,status FROM gm_question WHERE news_id = :news_id LIMIT 1';
                $questionDetail = $defaultDb->rawQuery($sql, [':news_id' => $v['obj_id'], 'status' => GmQuestion::STATUS_NORMAL])->rawFetchOne();
                if (empty($questionDetail)) {
                    YCore::exception(STATUS_ERROR, '该题目已经删除');
                }
                break;
        }
        $where = [
            'user_id'  => $userId,
            'obj_type' => $objType,
            'obj_id'   => $objId,
            'status'   => Favorites::STATUS_NORMAL
        ];
        $FavoritesModel  = new Favorites();
        $favoritesDetail = $FavoritesModel->fetchOne([], $where);
        if (!empty($favoritesDetail)) {
            return true;
        }
        $data = [
            'user_id'      => $userId,
            'obj_type'     => $objType,
            'obj_id'       => $objId,
            'status'       => Favorites::STATUS_NORMAL,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $FavoritesModel->insert($data);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        return true;
    }

    /**
     * 删除收藏。
     *
     * @param  int   $userId  用户ID。
     * @param  int   $objType 收藏类型：1商品收藏、2文章收藏、3友情链接、4题库、5培训资料文章。
     * @param  int   $objId   商品ID/文章ID。
     * @return void
     */
    public static function delete($userId, $objType, $objId)
    {
        $FavoritesModel = new Favorites();
        $where = [
            'user_id'  => $userId,
            'obj_type' => $objType,
            'obj_id'   => $objId,
            'status'   => Favorites::STATUS_NORMAL
        ];
        $ok = $FavoritesModel->delete($where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }
}
