<?php
/**
 * 题库管理。
 * @author fingerQin
 * @date 2018-01-08
 */

namespace services;

use finger\Validator;
use finger\DbBase;
use common\YCore;
use common\YUrl;
use services\GoldService;
use services\FavoritesService;
use models\GmQuestion;
use models\User;
use models\GmQuestionRecord;
use models\Category;
use models\Favorites;

class QuestionService extends AbstractService
{
    const SORT_TYPE_ASC    = 'asc';    // 按照题目 ID 顺序练习。
    const SORT_TYPE_RANDOM = 'random'; // 随机取题目。

    /**
     * 选项。
     * @var array
     */
    public static $options = [
        'A' => 'A选项',
        'B' => 'B选项',
        'C' => 'C选项',
        'D' => 'D选项',
        'E' => 'E选项'
    ];

    /**
     * 获取题目详情。
     * 
     * @param  int $questionId 竞猜ID。
     * 
     * @return array
     */
    public static function getAdminQuestionDetail($questionId)
    {
        $where = [
            'question_id' => $questionId,
            'status'      => GmQuestion::STATUS_NORMAL
        ];
        $columns = [
            'question_id', 'title', 'image_url', 'option_data', 'answer', 'explain', 'cat_code'
        ];
        $QuestionModel = new GmQuestion();
        $QuestionInfo  = $QuestionModel->fetchOne($columns, $where);
        if (empty($QuestionInfo)) {
            YCore::exception(STATUS_ERROR, '题目不存在');
        }
        $QuestionInfo['option_data'] = json_decode($QuestionInfo['option_data'], true);
        $QuestionInfo['answer']      = \explode(',', $QuestionInfo['answer']);
        // 如果已经绑定了分类。则读取父分类 ID。
        if (strlen($QuestionInfo['cat_code']) > 0) {
            $CategoryModel = new Category();
            $catInfo = $CategoryModel->fetchOne([], ['cat_code' => $QuestionInfo['cat_code']]);
            $QuestionInfo['parent_id'] = $catInfo ? $catInfo['parentid'] : 0;
        } else {
            $QuestionInfo['parent_id'] = 0;
        }
        return $QuestionInfo;
    }

    /**
     * 用户获取自己答题记录。
     * 
     * @param  int   $userId  用户ID。
     * @param  int   $page    当前页码。
     * @param  int   $count   每页显示条数。
     * 
     * @return array
     */
    public static function getUserQuestionRecordList($userId, $page = 1, $count = 20)
    {
        $offset     = self::getPaginationOffset($page, $count);
        $fromTable  = ' FROM gm_question_record ';
        $columns    = ' id, question_id, user_id, user_answer, right_or_no, created_time ';
        $where      = ' WHERE user_id = :user_id AND status = :status ';
        $params     = [
            ':user_id' => $userId,
            ':status'  => GmQuestion::STATUS_NORMAL
        ];
        $orderBy    = ' ORDER BY id DESC ';
        $sql        = "SELECT COUNT(1) AS count {$fromTable} {$where}";
        $defaultDb  = new DbBase();
        $countData  = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total      = $countData ? $countData['count'] : 0;
        $sql        = "SELECT {$columns} {$fromTable} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list       = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        $GuessModel = new GmQuestion();
        $guesss     = [];
        foreach ($list as $key => $item) {
            if (isset($guesss[$item['question_id']])) {
                $guessInfo = $guesss[$item['question_id']];
            } else {
                $guessInfo = $GuessModel->fetchOne([], ['question_id' => $item['question_id']]);
                $guesss[$item['question_id']] = $guessInfo;
            }
            $item['title']       = $guessInfo['title'];
            $item['option_data'] = $guessInfo['option_data'];
            $item['answer']      = $guessInfo['answer'];
            $list[$key]          = $item;
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
     * 获取答题列表。
     * 
     * --1、当选择指定分类的时候，把分类下所有的题目取出来。
     * --2、当没有指定分类的时候，从当前题库中取20道题目。
     *
     * @param  string $catId    指定分类ID。为 0 代表随机。随机抽取 20 道。
     * @param  string $sortType 排序类型。顺序、随机两种。无分类模式只有随机模式。
     * @return array
     */
    public static function getQuestionList($catId = 0, $sortType = self::SORT_TYPE_ASC)
    {
        if (strlen($catId) === 0) {
            return self::randomAllQuestion($catId);
        } else if ($sortType == self::SORT_TYPE_ASC) {
            return self::filterCategoryQuestion($catId, $sortType);
        }
    }

    /**
     * 从整个题库随机指定数量的题目。
     * 
     * @param int $count 获取的题目数量。
     * 
     * @return array
     */
    protected static function randomAllQuestion($count = 20)
    {
        $where = [
            'status' => GmQuestion::STATUS_NORMAL
        ];
        $sql = "SELECT question_id,title,image_url,option_data,answer,explain "
             . "FROM gm_question WHERE status = :status ORDER BY rand() LIMIT {$count}";
        $defaultDb = new DbBase();
        return $defaultDb->rawQuery($sql, $where)->rawFetchAll();
    }

    /**
     * 根据分类取题目数据。
     * 
     * @param  string $catId    分类ID。
     * @param  string $sortType 排序类型。顺序、随机两种。
     * @param  int    $count    显示条数。
     * @return array
     */
    protected static function filterCategoryQuestion($catId, $sortType, $count = 20)
    {
        $CategoryModel = new Category();
        $catInfo = $CategoryModel->fetchOne([], ['cat_id' => $catId]);
        if (empty($catInfo)) {
            YCore::exception(STATUS_ERROR, '服务器异常');
        }
        $catCode       = $catInfo['cat_code'];
        $QuestionModel = new GmQuestion();
        $columns       = ['question_id', 'title', 'image_url', 'option_data', 'answer', 'explain'];
        $where         = ['status' => Category::STATUS_NORMAL, 'cat_code' => $catCode];
        $result        = $QuestionModel->fetchAll($columns, $where, $count, 'question_id ASC');
        foreach ($result as $k => $item) {
            $result[$k]['option_data'] = json_decode($item['option_data'], true);
            $result[$k]['image_url']   = YUrl::filePath($item['image_url']);
        }
        if ($sortType == self::SORT_TYPE_RANDOM) {
            shuffle($result);
        }
        return $result;
    }

    /**
     * 全局题库取数据。
     *
     * --1、全部题库顺序练习没有任何意义。随机才能体验得特别的感觉。
     *
     * @param  int  $count  条数。
     * 
     * @return array
     */
    protected static function globalQuestion($count = 20)
    {
        $QuestionModel = new GmQuestion();
        $questionCount = $QuestionModel->count(['status' => GmQuestion::STATUS_NORMAL]);
        $randVals      = YCore::randomIntegerScope(1, $questionCount, $count);
        $columns       = ['question_id', 'title', 'image_url', 'option_data', 'answer', 'explain'];
        $where         = ['status' => GmQuestion::STATUS_NORMAL, 'question_id' => $randVals];
        $result        = $QuestionModel->fetchAll($columns, $where);
        shuffle($result);
        return $result;
    }

    /**
     * 用户获取当前答题题目列表。
     * 
     * @param  int $page    当前页码。
     * @param  int $count   每页显示条数。
     * 
     * @return array
     */
    public static function getUserQuestionList($page = 1, $count = 20)
    {
        $offset    = self::getPaginationOffset($page, $count);
        $fromTable = ' FROM gm_question ';
        $columns   = ' question_id, title, image_url, answer, option_data, explain ';
        $where     = ' WHERE status = :status ';
        $params    = [
            ':status' => GmQuestion::STATUS_NORMAL
        ];
        $orderBy   = ' ORDER BY question_id DESC ';
        $sql       = "SELECT COUNT(1) AS count {$fromTable} {$where}";
        $defaultDb = new DbBase();
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} {$fromTable} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
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
     * 管理后台获取用户答题记录。
     * 
     * @param  int      $questionId    题目ID。
     * @param  string   $username      用户账号。
     * @param  string   $mobilephone   用户手机号。
     * @param  int      $page          当前页码。
     * @param  int      $count         每页显示条数。
     * 
     * @return array
     */
    public static function getAdminQuestionRecordList($questionId = -1, $username = '', $mobilephone = '', $page = 1, $count = 20)
    {
        $offset    = self::getPaginationOffset($page, $count);
        $fromTable = ' FROM gm_question_record ';
        $columns   = ' question_id, user_id, right_or_no, user_answer, created_time ';
        $where     = ' WHERE status = :status ';
        $params    = [
            ':status' => GmQuestion::STATUS_NORMAL
        ];
        if ($questionId != GmQuestion::NONE) {
            $where .= ' AND question_id = :question_id ';
            $params[':question_id'] = $questionId;
        }
        $UserModel = new User();
        if (strlen($username) > 0) {
            $userinfo = $UserModel->fetchOne([], ['username' => $username]);
            $where   .= ' AND user_id = :user_id ';
            $params[':user_id'] = $userinfo ? $userinfo['user_id'] : 0;
        } else if (strlen($mobilephone) > 0) {
            $userinfo = $UserModel->fetchOne([], ['mobilephone' => $mobilephone]);
            $where   .= ' AND user_id = :user_id ';
            $params[':user_id'] = $userinfo ? $userinfo['user_id'] : 0;
        }
        $orderBy   = ' ORDER BY id DESC ';
        $sql       = "SELECT COUNT(1) AS count {$fromTable} {$where}";
        $defaultDb = new DbBase();
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} {$fromTable} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        $users     = [];
        foreach ($list as $key => $item) {
            if (isset($users[$item['user_id']])) {
                $userinfo = $users[$item['user_id']];
            } else {
                $userinfo = $UserModel->fetchOne([], ['user_id' => $item['user_id']]);
                $users[$item['user_id']] = $userinfo;
            }
            $item['username']    = $userinfo['username'];
            $item['mobilephone'] = $userinfo['mobilephone'];
            $list[$key]          = $item;
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
     * 管理后台获取题库题目列表。
     * 
     * @param  string   $catCode      题目分类。
     * @param  string   $title        题目标题。
     * @param  string   $startTime    创建时间开始。
     * @param  string   $endTime      创建时间截止。
     * @param  int      $page         当前页码。
     * @param  int      $count        每页显示条数。
     * 
     * @return array
     */
    public static function getAdminQuestionList($catCode = '', $title = '', $startTime = '', $endTime = '', $page = 1, $count = 20)
    {
        $offset    = self::getPaginationOffset($page, $count);
        $fromTable = ' FROM gm_question ';
        $columns   = ' question_id, title, image_url, cat_code, option_data, answer, total_ok_time, '
                   . 'total_error_times, total_times, total_people, modified_time, created_time ';
        $where     = ' WHERE status = :status ';
        $params    = [
            ':status' => GmQuestion::STATUS_NORMAL
        ];
        if (strlen($catCode) > 0) {
            $where .= ' AND catCode = :catCode ';
            $params[':catCode'] = $catCode;
        } 
        if (strlen($title) > 0) {
            $where .= ' AND title LIKE :title ';
            $params[':title'] = "%{$title}%";
        }
        if (strlen($startTime) > 0) {
            $where .= ' AND created_time >= :start_time ';
            $params[':start_time'] = strtotime($startTime);
        }
        if (strlen($endTime) > 0) {
            $where .= ' AND created_time <= :end_time ';
            $params[':end_time'] = $endTime;
        }
        $orderBy   = ' ORDER BY question_id DESC ';
        $sql       = "SELECT COUNT(1) AS count {$fromTable} {$where}";
        $defaultDb = new DbBase();
        $countData = $defaultDb->rawQuery($sql, $params)->rawFetchOne();
        $total     = $countData ? $countData['count'] : 0;
        $sql       = "SELECT {$columns} {$fromTable} {$where} {$orderBy} LIMIT {$offset},{$count}";
        $list      = $defaultDb->rawQuery($sql, $params)->rawFetchAll();
        foreach ($list as $key => $item) {
            $item['image_url']     = YUrl::filePath($item['image_url']);
            $item['created_time']  = YCore::formatDateTime($item['created_time']);
            $item['modified_time'] = YCore::formatDateTime($item['modified_time']);
            $list[$key]            = $item;
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
     * 添加题目。
     * -- Example start --
     * $options_data = [
     *     [
     *         'op_title' => '选项标题'
     *     ],
     *     [
     *         'op_title' => '选项标题'
     *     ],
     *     ......
     * ];
     * -- Example end --
     * @param  int     $adminId      管理员ID。
     * @param  string  $title        题目标题。
     * @param  string  $imageUrl     题目图片。
     * @param  array   $optionsData  题目选项数据。
     * @param  string  $catCode      题目分类
     * @param  string  $answer       正确答案。
     * @param  string  $explain      题目解析。
     * @return void
     */
    public static function addQuestion($adminId, $title, $imageUrl, $optionsData, $catCode, $answer, $explain = '')
    {
        if (strlen($title) === 0) {
            YCore::exception(STATUS_ERROR, '题目标题必须填写');
        }
        if (!Validator::is_len($title, 1, 255, true)) {
            YCore::exception(STATUS_ERROR, '题目标题必须1至255个字符 ');
        }
        if (strlen($catCode) === 0) {
            YCore::exception(STATUS_ERROR, '题目分类必须选择');
        }
        if (empty($optionsData)) {
            YCore::exception(STATUS_ERROR, '题目选项必须设置');
        }
        if (empty($answer)) {
            YCore::exception(STATUS_ERROR, '正确答案必须选择');
        }
        foreach ($optionsData as $opk => $item) {
            if (strlen($item['op_title']) === 0) {
                continue;
            }
            if (!isset($item['op_title'])) {
                YCore::exception(STATUS_ERROR, '选项内容必须填写');
            }
            if (!Validator::is_len($item['op_title'], 1, 50, true)) {
                YCore::exception(STATUS_ERROR, '选项内容不能超过50个字符');
            }
        }
        $data = [
            'title'        => $title,
            'image_url'    => $imageUrl,
            'option_data'  => json_encode($optionsData),
            'cat_code'     => $catCode,
            'status'       => GmQuestion::STATUS_NORMAL,
            'explain'      => $explain,
            'answer'       => $answer,
            'total_people' => 0,
            'created_by'   => $adminId,
            'created_time' => date('Y-m-d H:i:s', time())
        ];
        $GuessModel = new GmQuestion();
        $ok = $GuessModel->insert($data);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 编辑题目。
     * 
     * -- Example start --
     * $options_data = [
     *     [
     *         'op_title' => '选项标题'
     *     ],
     *     [
     *         'op_title' => '选项标题'
     *     ],
     *     ......
     * ];
     * -- Example end --
     * @param  int     $adminId      管理员ID。
     * @param  int     $questionId   题目ID。
     * @param  string  $title        题目标题。
     * @param  string  $imageUrl     题目图片。
     * @param  array   $optionsData  题目选项数据。
     * @param  string  $catCode      题目分类
     * @param  string  $answer       正确答案。
     * @param  string  $explain      题目解析。
     * 
     * @return void
     */
    public static function editQuestion($adminId, $questionId, $title, $imageUrl, $optionsData, $catCode, $answer, $explain = '')
    {
        if (strlen($title) === 0) {
            YCore::exception(STATUS_ERROR, '题目标题必须填写');
        }
        if (!Validator::is_len($title, 1, 255, true)) {
            YCore::exception(STATUS_ERROR, '题目标题必须1至255个字符 ');
        }
        if (strlen($catCode) === 0) {
            YCore::exception(STATUS_ERROR, '题目所属分类必须选择');
        }
        if (empty($answer)) {
            YCore::exception(STATUS_ERROR, '正确答案必须选择');
        }
        if (empty($optionsData)) {
            YCore::exception(STATUS_ERROR, '题目选项必须设置');
        }
        foreach ($optionsData as $item) {
            if (strlen($item['op_title']) === 0) {
                continue;
            }
            if (!isset($item['op_title'])) {
                YCore::exception(STATUS_ERROR, '选项内容必须填写');
            }
            if (!Validator::is_len($item['op_title'], 1, 50, true)) {
                YCore::exception(STATUS_ERROR, '选项内容不能超过50个字符');
            }
        }
        $where = [
            'question_id' => $questionId,
            'status'      => GmQuestion::STATUS_NORMAL
        ];
        $QuestionModel = new GmQuestion();
        $questionInfo  = $QuestionModel->fetchOne([], $where);
        if (empty($questionInfo)) {
            YCore::exception(STATUS_ERROR, '题目不存在');
        }
        $data = [
            'title'         => $title,
            'image_url'     => $imageUrl,
            'option_data'   => json_encode($optionsData),
            'cat_code'      => $catCode,
            'status'        => GmQuestion::STATUS_NORMAL,
            'answer'        => $answer,            
            'explain'       => $explain,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $QuestionModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 删除题目。
     *
     * @param  int  $adminId    管理员ID。
     * @param  int  $questionId 题目ID。
     *
     * @return void 
     */
    public static function deleteQuestion($adminId, $questionId)
    {
        YCore::exception(STATUS_ERROR, '当前只允许修改题目不允许删除');
        $where = [
            'question_id' => $questionId,
            'status'      => GmQuestion::STATUS_NORMAL
        ];
        $QuestionModel = new GmQuestion();
        $questionInfo  = $QuestionModel->fetchOne([], $where);
        if (empty($guessInfo)) {
            YCore::exception(STATUS_ERROR, '题目不存在');
        }
        $data = [
            'status'        => GmQuestion::STATUS_DELETED,
            'modified_by'   => $adminId,
            'modified_time' => date('Y-m-d H:i:s', time())
        ];
        $ok = $QuestionModel->update($data, $where);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }

    /**
     * 用户答题。
     * 
     * @param  int     $userId      用户ID。
     * @param  int     $questionId  题目ID。
     * @param  string  $optionIndex 用户选择的选项。A,B,C。有单选多选情况。
     * 
     * @return void
     */
    public static function startDo($userId, $questionId, $userAnswer)
    {
        $datetime    = date('Y-m-d H:i:s', time());
        $userAnswer  = self::userAnswerSort($userAnswer);
        $GuessModel  = new GmQuestion();
        $guessDetail = $GuessModel->fetchOne([], ['question_id' => $questionId, 'status' => GmQuestion::STATUS_NORMAL]);
        if (empty($guessDetail)) {
            YCore::exception(STATUS_ERROR, '题目不存在');
        }
        $isRight = ($guessDetail['answer'] != $userAnswer) ? 1 : 0;
        $QuestionRecordModel = new GmQuestionRecord();
        $data = [
            'question_id'  => $questionId,
            'user_id'      => $userId,
            'user_answer'  => $userAnswer,
            'right_or_no'  => $isRight,
            'status'       => GmQuestion::STATUS_NORMAL,
            'created_time' => $datetime
        ];
        $ok = $QuestionRecordModel->insert($data);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
        if ($isRight == 0) {
            YCore::exception(STATUS_ERROR, '您回答错误!');
        }
    }

    /**
     * 对用户答案进行排序。
     *
     * @param  string  $answer 用户答案。B,A,C => A,B,C
     * 
     * @return string
     */
    public static function userAnswerSort($answer)
    {
        if (strlen($answer) === 0) {
            return '';
        }
        $arr      = \explode(',', $answer);
        $afterArr = sort($arr);
        return \implode(',', $afterArr);
    }

    /**
     * 题目收藏。
     *
     * @param  int  $userId         用户ID。
     * @param  int  $questionId     题目ID。
     * @return void
     */
    public static function favoriteDo($userId, $questionId)
    {
        FavoritesService::add($userId, Favorites::OBJ_TYPE_QUESTION, $questionId);
    }
}
