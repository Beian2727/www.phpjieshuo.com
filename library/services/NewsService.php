<?php
/**
 * 文章管理。
 * @author fingerQin
 * @date 2016-03-28
 */

namespace services;

use finger\Validator;
use finger\DbBase;
use common\YCore;
use common\YUrl;
use models\News;
use models\NewsData;
use models\Admin;
use models\Category;

class NewsService extends AbstractService 
{
    /**
     * 获取指定文档的目录。
     *
     * @param  int  $catId  文档分类 ID。
     * @return void
     */
    public static function getDocCatalogue($catId)
    {
        $catList    = CategoryService::getByParentToCategory($catId, CategoryService::CAT_DOC);
        $arrCatCode = array_column($catList, 'cat_code');
        $NewsModel  = new News();
        $columns    = ['news_id', 'cat_code', 'title', 'intro', 'keywords', 'image_url', 'source', 'modified_time'];
        $where      = [
            'cat_code' => ['IN', $arrCatCode],
            'status'   => Category::STATUS_NORMAL,
            'display'  => Category::STATUS_YES
        ];
        $newsList = $NewsModel->fetchAll($columns, $where, 0, 'listorder ASC');
        $result   = [];
        foreach ($catList as $catInfo) {
            $catCodeNews = [];
            foreach ($newsList as $news) {
                if ($news['cat_code'] == $catInfo['cat_code']) {
                    $catCodeNews[] = $news;
                }
            }
            $result[$catInfo['cat_code']] = [
                'catInfo' => $catInfo,
                'news'    => $catCodeNews
            ];
        }
        return $result;
    }

    /**
     * 获取新闻列表。
     *
     * @param  string   $keywords   关键词。
     * @param  string   $catCode    分类编码。
     * @param  int      $page       页码。
     * @param  int      $count      每页显示条数。
     * 
     * @return array
     */
    public static function getNewsList($keywords, $catCode, $page, $count = 20)
    {
        $offset  = self::getPaginationOffset($page, $count);
        $columns = ' news_id,cat_code,title,intro,image_url,keywords,source,hits,created_time ';
        $where   = ' WHERE status = :status ';
        $params  = [
            ':status' => News::STATUS_NORMAL
        ];
        if (strlen($keywords) > 0) {
            $where .= ' AND title LIKE :title ';
            $params[':title'] = "%{$keywords}%";
        }
        if (strlen($catCode) > 0) {
            $where .= ' AND cat_code = :cat_code ';
            $params[':cat_code'] = $catCode;
        }
        $defaultDb = new DbBase();
        $orderBy   = ' ORDER BY news_id DESC ';
        $sql       = "SELECT COUNT(1) AS count FROM ms_news {$where}";
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} FROM ms_news {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        foreach ($list as $key => $news) {
            $catInfo           = CategoryService::getCategoryByCatCode($news['cat_code']);
            $news['image_url'] = YUrl::filePath($news['image_url']);
            $news['cat_id']    = $catInfo['cat_id'];
            $list[$key]        = $news;
        }
        $result    = [
            'list'   => $list,
            'total'  => $total,
            'page'   => $page,
            'count'  => $count,
            'isnext' => self::IsHasNextPage($total, $page, $count)
        ];
        return $result;
    }

    /**
     * 文章列表。
     *
     * @param  string  $title      文章标题。
     * @param  string  $adminName  管理员账号。
     * @param  string  $starttime  开始时间。
     * @param  string  $endtime    截止时间。
     * @param  int     $page       分页页码。
     * @param  int     $count      每页显示记录条数。
     * 
     * @return array
     */
    public static function getAdminNewsList($title = '', $adminName = '', $starttime = '', $endtime = '', $page = 1, $count = 20)
    {
        if (strlen($starttime) > 0 && !Validator::is_date($starttime, 'Y-m-d H:i:s')) {
            YCore::exception(STATUS_ERROR, '开始时间格式不对');
        }
        if (strlen($endtime) > 0 && !Validator::is_date($endtime, 'Y-m-d H:i:s')) {
            YCore::exception(STATUS_ERROR, '结束时间格式不对');
        }
        if (mb_strlen($title) > 100) {
            YCore::exception(STATUS_ERROR, '标题查询条件长度不能大于100个字符');
        }
        $newsCategoryList = CategoryService::getCategoryCatCodeOrNameKeyValueList(0, CategoryService::CAT_DOC);
        $adminId = -1;
        if (strlen($adminName) > 0) {
            $AdminModel = new Admin();
            $admin      = $AdminModel->fetchOne([], ['username' => $adminName]);
            $adminId    = $admin ? $admin['admin_id'] : 0;
        }
        $NewsModel = new News();
        $result    = $NewsModel->getList($title, $adminId, $starttime, $endtime, $page, $count);
        foreach ($result['list'] as $key => $item) {
            $item['cat_name']     = isset($newsCategoryList[$item['cat_code']]) ? $newsCategoryList[$item['cat_code']] : '-';
            $result['list'][$key] = $item;
        }
        unset($NewsModel, $newsCategoryList);
        return $result;
    }

    /**
     * 按文章code获取文章详情。
     *
     * @param  string $code            文章code。
     * @param  bool   $isGetContent    是否获取文章内容。false：否、true：是。
     * @return array
     */
    public static function getByCodeNewsDetail($code, $isGetContent = false)
    {
        $NewsModel = new News();
        $data      = $NewsModel->fetchOne([], ['code' => $code, 'status' => News::STATUS_NORMAL]);
        if (empty($data)) {
            YCore::exception(STATUS_ERROR, '文章不存在或已经删除');
        }
        if ($isGetContent) {
            $NewsDataModel = new NewsData();
            $newsData      = $NewsDataModel->fetchOne([], ['news_id' => $data['news_id']]);
            if ($newsData) {
                return array_merge($data, $newsData);
            } else {
                YCore::exception(STATUS_SERVER_ERROR, '文章数据异常');
            }
        } else {
            return $data;
        }
    }

    /**
     * 按文章ID获取文章详情。
     *
     * @param  int   $newsId        文章ID。
     * @param  bool  $isGetContent  是否获取文章内容。false：否、true：是。
     * @return array
     */
    public static function getNewsDetail($newsId, $isGetContent = false)
    {
        $NewsModel = new News();
        $data      = $NewsModel->fetchOne([], ['news_id' => $newsId, 'status' => News::STATUS_NORMAL]);
        if (empty($data)) {
            YCore::exception(STATUS_ERROR, '文章不存在或已经删除');
        }
        // 如果已经绑定了分类。则读取父分类 ID。
        if (strlen($data['cat_code']) > 0) {
            $CategoryModel     = new Category();
            $catInfo           = $CategoryModel->fetchOne([], ['cat_code' => $data['cat_code']]);
            $data['parent_id'] = $catInfo ? $catInfo['parentid'] : 0;
        } else {
            $data['parent_id'] = 0;
        }
        if ($isGetContent) {
            $NewsDataModel = new NewsData();
            $newsData      = $NewsDataModel->fetchOne([], ['news_id' => $newsId]);
            if ($newsData) {
                return array_merge($data, $newsData);
            } else {
                YCore::exception(STATUS_SERVER_ERROR, '文章数据异常');
            }
        } else {
            return $data;
        }
    }

    /**
     * 按文章ID获取文章详情(如果不存在返回完整结构的空数据)。
     *
     * @param  int   $newsId        文章ID。
     * @param  bool  $isGetContent  是否获取文章内容。false：否、true：是。
     * @return array
     */
    public static function getNewsDetailOrDefault($newsId, $isGetContent = false)
    {
        $columns   = ['news_id', 'title', 'keywords', 'source', 'hits', 'cat_code'];
        $NewsModel = new News();
        $data      = $NewsModel->fetchOne($columns, ['news_id' => $newsId, 'status' => News::STATUS_NORMAL]);
        if (empty($data)) {
            return [
                'news_id'  => 0,
                'title'    => '',
                'keywords' => '',
                'source'   => '',
                'hits'     => '',
                'content'  => ''
            ];
        } else {
            // 如果已经绑定了分类。则读取父分类 ID。
            if (strlen($data['cat_code']) > 0) {
                $CategoryModel     = new Category();
                $catInfo           = $CategoryModel->fetchOne([], ['cat_code' => $data['cat_code']]);
                $data['parent_id'] = $catInfo ? $catInfo['parentid'] : 0;
            } else {
                $data['parent_id'] = 0;
            }
            if ($isGetContent) {
                $NewsDataModel = new NewsData();
                $newsData      = $NewsDataModel->fetchOne([], ['news_id' => $newsId]);
                if ($newsData) {
                    return array_merge($data, $newsData);
                } else {
                    YCore::exception(STATUS_SERVER_ERROR, '文章数据异常');
                }
            } else {
                return $data;
            }
        }
    }

    /**
     * 按文章编码获取文章详情。
     *
     * @param  string $code 文章编码。
     * @return array
     */
    public static function getByCodeDetail($code)
    {
        $NewsModel = new News();
        $data      = $NewsModel->fetchOne([], ['code' => $code, 'status' => News::STATUS_NORMAL]);
        if (empty($data)) {
            YCore::exception(STATUS_ERROR, '文章不存在或已经删除');
        }
        $NewsDataModel = new NewsData();
        $newsData      = $NewsDataModel->fetchOne([], ['news_id' => $data['news_id']]);
        if ($newsData) {
            return array_merge($data, $newsData);
        } else {
            YCore::exception(STATUS_SERVER_ERROR, '文章数据异常');
        }
    }

    /**
     * 添加文章。
     *
     * @param  int     $adminId   管理员ID。
     * @param  int     $catCode   分类编码。
     * @param  string  $title     文章标题。
     * @param  string  $intro     文章简介。
     * @param  string  $keywords  文章关键词。
     * @param  string  $source    文章来源。
     * @param  string  $imageUrl  文章图片。
     * @param  string  $content   文章内容。
     * @param  int     $display   显示状态：1显示、0隐藏。
     * @return void
     */
    public static function addNews($adminId, $catCode, $title, $intro, $keywords, $source, $imageUrl, $content, $display = 1)
    {
        $CategoryModel = new Category();
        $catInfo       = $CategoryModel->fetchOne([], ['cat_code' => $catCode, 'status' => News::STATUS_NORMAL]);
        if (empty($catInfo)) {
            YCore::exception(STATUS_ERROR, '分类不存在或已经删除');
        }
        
        $data = [
            'title'     => $title,
            'intro'     => $intro,
            'keywords'  => $keywords,
            'source'    => $source,
            'image_url' => $imageUrl,
            'content'   => $content,
            'display'   => $display
        ];
        $rules = [
            'title'     => '标题|require|len:1:80:1',
            'intro'     => '文章简介|require|len:1:500:1',
            'keywords'  => '文章关键词|require|len:1:100:1',
            'source'    => '文章来源|require|len:1:50:1',
            'image_url' => '文章图片|len:1:100:1',
            'content'   => '文章内容|require|len:10:500000:1',
            'display'   => '显示状态|require|integer'
        ];
        Validator::valido($data, $rules);
        $data['created_by']   = $adminId;
        $data['created_time'] = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $data['cat_code']     = $catCode;
        $data['status']       = News::STATUS_NORMAL;
        unset($data['content']);
        $NewsModel = new News();
        $newsId    = $NewsModel->insert($data);
        if ($newsId > 0) {
            $newsDataModel = new NewsData();
            $data = [
                'content' => $content,
                'news_id' => $newsId
            ];
            $newsDataModel->insert($data);
            return true;
        } else {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 文章编辑。
     *
     * @param  int     $adminId   管理员ID。
     * @param  int     $newsId    文章ID。
     * @param  int     $catCode   分类ID。
     * @param  string  $title     文章标题。
     * @param  string  $intro     文章简介。
     * @param  string  $keywords  文章关键词。
     * @param  string  $source    文章来源。
     * @param  string  $imageUrl  文章图片。
     * @param  string  $content   文章内容。
     * @param  int     $display   显示状态：1显示、0隐藏。
     * @return void
     */
    public static function editNews($adminId, $newsId, $catCode, $title, $intro, $keywords, $source, $imageUrl, $content, $display = 1)
    {
        $NewsModel  = new News();
        $newsDetail = $NewsModel->fetchOne([], ['news_id' => $newsId, 'status' => News::STATUS_NORMAL]);
        if (empty($newsDetail)) {
            YCore::exception(STATUS_ERROR, '文章不存在或已经删除');
        }
        $CategoryModel = new Category();
        $catInfo       = $CategoryModel->fetchOne([], ['cat_code' => $catCode, 'status' => News::STATUS_NORMAL]);
        if (empty($catInfo)) {
            YCore::exception(STATUS_ERROR, '分类不存在或已经删除');
        }
        $data = [
            'title'     => $title,
            'intro'     => $intro,
            'keywords'  => $keywords,
            'source'    => $source,
            'image_url' => $imageUrl,
            'content'   => $content,
            'display'   => $display
        ];
        $rules = [
            'title'     => '标题|require|len:1:80:1',
            'intro'     => '文章简介|require|len:1:500:1',
            'keywords'  => '文章关键词|require|len:1:100:1',
            'source'    => '文章来源|require|len:1:50:1',
            'image_url' => '文章图片|len:1:100:1',
            'content'   => '文章内容|require|len:10:500000:1',
            'display'   => '显示状态|require|integer'
        ];
        Validator::valido($data, $rules); // 验证不通过会抛异常。
        $data['modified_by']   = $adminId;
        $data['modified_time'] = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $data['cat_code']      = $catCode;
        unset($data['content']);
        $ok = $NewsModel->update($data, ['news_id' => $newsId, 'status' => News::STATUS_NORMAL]);
        if ($ok) {
            $NewsDataModel = new NewsData();
            $data = [
                'content' => $content
            ];
            $where = [
                'news_id' => $newsId
            ];
            $NewsDataModel->update($data, $where);
            return true;
        } else {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 删除文章。
     *
     * @param  int  $adminId 管理员ID。
     * @param  int  $newsId  文章ID。
     * @return bool
     */
    public static function deleteNews($adminId, $newsId)
    {
        $NewsModel  = new News();
        $NewsDetail = $NewsModel->fetchOne([], ['news_id' => $newsId, 'status' => News::STATUS_NORMAL]);
        if (empty($NewsDetail)) {
            YCore::exception(STATUS_ERROR, '文章不存在或已经删除');
        }
        $where = [
            'news_id' => $newsId
        ];
        $data = [
            'status'        => News::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
        ];
        $ok = $NewsModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 文章排序。
     *
     * @param  int   $adminId    管理员ID。
     * @param  array $listorders 分类排序数据。[ ['文章ID' => '排序值'], ...... ]
     * @return bool
     */
    public static function sortNews($adminId, $listorders)
    {
        if (empty($listorders)) {
            YCore::exception(STATUS_ERROR, '请选择要排序的文章');
        }
        $NewsModel = new News();
        foreach ($listorders as $newsId => $sortValue) {
            $data = [
                'listorder'     => $sortValue,
                'modified_by'   => $adminId,
                'modified_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
            ];
            $where = [
                'news_id' => $newsId,
                'status'  => News::STATUS_NORMAL
            ];
            $NewsModel->update($data, $where);
        }
    }

}
