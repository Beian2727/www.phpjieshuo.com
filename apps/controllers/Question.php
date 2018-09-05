<?php
/**
 * 答题管理。
 * @author fingerQin
 * @date 2018-01-11
 */

use services\CategoryService;
use services\QuestionService;

class QuestionController extends \common\controllers\User
{
    /**
     * 答题首页。
     */
    public function indexAction()
    {
        $catList = CategoryService::getCategoryList(0, CategoryService::CAT_QUESTION, true);
        $this->assign('catList', $catList);
    }

    /**
     * 答题记录。
     */
    public function recordAction()
    {

    }

    /**
     * 我要答题。
     */
    public function startDoAction()
    {
        $catId = $this->getInt('cat_id');
        $this->assign('catId', $catId);
    }

    /**
     * 获取指定分类题目。
     */
    public function getAction()
    {
        $catId     = $this->getInt('cat_id');
        $questions = QuestionService::getQuestionList($catId);
        echo json_encode($questions);
        $this->end();
    }

    public function statsAction()
    {
        
    }

    /**
     * 收藏列表。
     */
    public function favoritesAction()
    {

    }

    /**
     * 取消收藏。
     */
    public function cancelFavoriteAction()
    {
        $this->end();
    }
}