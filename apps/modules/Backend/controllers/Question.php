<?php
/**
 * 题库管理。
 * @author fingerQin
 * @date 2018-01-08
 */

use finger\Paginator;
use common\YCore;
use services\QuestionService;
use services\CategoryService;

class QuestionController extends \common\controllers\Admin
{
    /**
     * 题目列表。
     */
    public function listAction()
    {
        $catCode   = $this->getString('cat_code', '');
        $title     = $this->getString('title', '');
        $startTime = $this->getString('start_time', '');
        $endTime   = $this->getString('end_time', '');
        $page      = $this->getInt(YCore::appconfig('pager'), 1);
        $list      = QuestionService::getAdminQuestionList($catCode, $title, $startTime, $endTime, $page, 20);
        $paginator = new Paginator($list['total'], 20);
        $pageHtml  = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $list['list']);
        $this->assign('cat_code', $catCode);
        $this->assign('title', $title);
        $this->assign('start_time', $startTime);
        $this->assign('end_time', $endTime);
    }

    /**
     * 添加题目。
     */
    public function addAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $title       = $this->getString('title');
            $imageUrl    = $this->getString('image_url', '');
            $optionsData = $this->getArray('options_data');
            $catCode     = $this->getString('cat_code', '');
            $explain     = $this->getString('explain', '');
            $answer      = $this->getArray('answer', []);
            sort($answer);
            $answer      = \implode(',', $answer);
            QuestionService::addQuestion($this->admin_id, $title, $imageUrl, $optionsData, $catCode, $answer, $explain);
            $this->json(true, '添加成功');
        }
        $catList = CategoryService::getCategoryList(0, CategoryService::CAT_QUESTION);
        $this->assign('options', QuestionService::$options);
        $this->assign('cat_list', $catList);
    }

    /**
     * 题目编辑。
     */
    public function editAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $questionId  = $this->getInt('question_id');
            $title       = $this->getString('title');
            $imageUrl    = $this->getString('image_url', '');
            $optionsData = $this->getArray('options_data');
            $catCode     = $this->getString('cat_code', '');
            $explain     = $this->getString('explain', '');
            $answer      = $this->getArray('answer', []);
            sort($answer);
            $answer      = \implode(',', $answer);
            QuestionService::editQuestion($this->admin_id, $questionId, $title, $imageUrl, $optionsData, $catCode, $answer, $explain);
            $this->json(true, '修改成功');
        }
        $questionId = $this->getInt('question_id');
        $detail     = QuestionService::getAdminQuestionDetail($questionId);
        $catList    = CategoryService::getCategoryList(0, CategoryService::CAT_QUESTION, true, true);
        $this->assign('detail', $detail);
        $this->assign('options', QuestionService::$options);
        $this->assign('cat_list', $catList);
    }

    /**
     * 题目删除。
     */
    public function deleteAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $questionId = $this->getInt('question_id');
            QuestionService::deleteQuestion($this->admin_id, $questionId);
            $this->json(true, '删除成功');
        }
    }

    /**
     * 参与记录。
     */
    public function recordAction()
    {
        $username    = $this->getString('username', '');
        $mobilephone = $this->getString('mobilephone', '');
        $page        = $this->getInt(YCore::appconfig('pager'), 1);
        $questionId  = $this->getInt('question_id');
        $list        = QuestionService::getAdminQuestionRecordList($questionId, $username, $mobilephone, $page, 15);
        $paginator   = new Paginator($list['total'], 20);
        $pageHtml    = $paginator->backendPageShow();
        $this->assign('page_html', $pageHtml);
        $this->assign('list', $list['list']);
        $this->assign('question_id', $questionId);
        $this->assign('username', $username);
        $this->assign('mobilephone', $mobilephone);
    }
}