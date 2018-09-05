<?php
/**
 * 公共controller。
 * --1、Yaf框架会根据特有的类名后缀(DbBase、Controller、Plugin)进行自动加载。为避免这种情况请不要以这样的名称结尾。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace common\controllers;

use common\YCore;
use finger\Validator;

class Common extends \Yaf_Controller_Abstract {

    /**
     * 配置文件对象。
     *
     * @var \Yaf_Config_Abstract
     */
    protected $_config = null;

    /**
     * 请求对象。
     *
     * @var \Yaf_Request_Http
     */
    protected $_request = null;

    /**
     * 视图对象。
     *
     * @var \Yaf_View_Simple
     */
    protected $_view = null;

    /**
     * session对象。
     *
     * @var \Yaf_Session
     */
    protected $_session = null;

    /**
     * MYSQL连接。
     *
     * @var \PDO
     */
    protected $_mysql = null;

    /**
     * 模块名称。
     *
     * @var string
     */
    protected $_moduleName = '';

    /**
     * 控制器名称。
     *
     * @var string
     */
    protected $_ctrlName = '';

    /**
     * 操作名称。
     *
     * @var string
     */
    protected $_actionName = '';

    /**
     * 该方法在所有Action执行之前执行。主要做一些初始化工作。
     */
    public function init() {
        $this->_view       = $this->getView();
        $this->_request    = $this->getRequest();
        $this->_session    = \Yaf_Registry::get('session');
        $this->_config     = \Yaf_Registry::get('config');
        $this->_mysql      = \Yaf_Registry::get('mysql');
        $this->_moduleName = $this->_request->getModuleName();
        $this->_ctrlName   = $this->_request->getControllerName();
        $this->_actionName = $this->_request->getActionName();
    }

    /**
     * 从请求中读取一个整型数值。
     * -- 1、如果该数据本身不是一个整型，将会抛异常。
     * -- 2、如果该数值不存在将会返回默认值。
     * -- 3、默认值也必须是整型。
     * -- 4、读取的值将从GPC(GET、POST)中读取。
     *
     * @param string $name
     * @param int $defaultValue
     */
    final protected function getInt($name, $defaultValue = null) {
        $gpValue = $this->getGP($name);
        if (is_null($gpValue)) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name}值异常");
            } else if (!Validator::is_integer($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name}默认值不是整型");
            } else {
                return $defaultValue;
            }
        } else {
            if (!Validator::is_integer($gpValue)) {
                YCore::exception(STATUS_ERROR, "{$name}值不是整型");
            } else {
                return $gpValue;
            }
        }
    }

    /**
     * 从请求中读取一个数组。
     * -- 1、如果该数据本身不是一个数组类型，将会抛异常。
     * -- 2、如果该数值不存在将会返回默认值。
     * -- 3、默认值也必须是数组类型。
     * -- 4、读取的值将从GPC(GET、POST)中读取。
     *
     * @param string $name
     * @param array $defaultValue
     */
    final protected function getArray($name, $defaultValue = null) {
        $gpValue = $this->getGP($name);
        if (is_null($gpValue)) {
            if (is_null($defaultValue)) {
                YCore::exception(4000004, "{$name}值异常");
            } else if (!is_array($defaultValue)) {
                YCore::exception(4000005, "default_valuec参数不是数组");
            } else {
                return $defaultValue;
            }
        } else {
            if (!is_array($gpValue)) {
                YCore::exception(4000006, "{$name}值不是数组");
            } else {
                return $gpValue;
            }
        }
    }

    /**
     * 从请求中读取一个浮点型数值。
     * -- 1、如果该数据本身不是一个浮点型，将会抛异常。
     * -- 2、如果该数值不存在将会返回默认值。
     * -- 3、默认值也必须是浮点型。
     * -- 4、读取的值将从GPC(GET、POST)中读取。
     *
     * @param string $name
     * @param int $defaultValue
     */
    final protected function getFloat($name, $defaultValue = null) {
        $gpValue = $this->getGP($name);
        if (is_null($gpValue)) {
            if (is_null($defaultValue)) {
                YCore::exception(4000007, "{$name}值异常");
            } else if (!Validator::is_float($defaultValue)) {
                YCore::exception(4000008, "default_valuec参数不是浮点型");
            } else {
                return $defaultValue;
            }
        } else {
            if (!Validator::is_float($gpValue)) {
                YCore::exception(4000009, "{$name}值不是浮点型");
            } else {
                return $gpValue;
            }
        }
    }

    /**
     * 从请求中读取一个字符串数值。
     * -- 1、如果该数值不存在将会返回默认值。
     * -- 2、读取的值将从GPC(GET、POST)中读取。
     * -- 3、数据会进行防注入处理。
     *
     * @param string $name
     * @param int $defaultValue
     */
    final protected function getString($name, $defaultValue = null) {
        $gpValue = $this->getGP($name);
        if (is_null($gpValue)) {
            if (is_null($defaultValue)) {
                YCore::exception(4000010, "{$name}值异常");
            } else {
                return $defaultValue;
            }
        } else {
            return $gpValue;
        }
    }

    /**
     * 获取GET、POST、路由里面的值。
     * -- 1、先读路由分解出来的参数、再读GET、其次读POST。
     *
     * @param  string  $name
     * @return mixed
     */
    final protected function getGP($name) {
        $value = $this->_request->getParam($name);
        if (is_array($value) && ! empty($value)) {
            return $value;
        }
        if (strlen($value) > 0) {
            return $value;
        }
        if (isset($_GET[$name])) {
            return $this->_request->getQuery($name);
        } else if (isset($_POST[$name])) {
            return $this->_request->getPost($name);
        } else {
            return null;
        }
    }

    /**
     * 关闭模板渲染。
     */
    public function end() {
        \Yaf_Dispatcher::getInstance()->autoRender(false);
    }

    /**
     * 模板传值(this->_view->assign())。
     *
     * -- 该方法是封装了 Yaf_View 提供的 assign() 方法。
     * 
     * @param  mixed  $name  字符串或者关联数组, 如果为字符串, 则$value不能为空, 此字符串代表要分配的变量名. 如果为数组, 则$value须为空, 此参数为变量名和值的关联数组.
     * @param  mixed  $value 分配的模板变量值
     * @return bool
     */
    public function assign($name, $value = null) {
        return $this->_view->assign($name, $value);
    }

    /**
     * 输出JSON到浏览器。
     *
     * @param string $message 提示信息。
     * @param array $data 返回的数据。如果不存在则连data键不会返回。
     * @return void
     */
    public function successJson($message, array $data = null) {
        $this->json(true, $message, $data);
    }

    /**
     * 输出JSON到浏览器。
     *
     * @param boolean $status 操作成功与否。true:成功、false：失败。
     * @param string $message 提示信息。
     * @param array $data 返回的数据。如果不存在则连data键不会返回。
     * @return void
     */
    public function json($status, $message, array $data = null) {
        $result = [
            'msg' => $message
        ];
        if ($status) {
            $result['code'] = STATUS_SUCCESS;
        } else {
            $result['code'] = STATUS_ERROR;
        }
        if (!is_null($data)) {
            $result['data'] = $data;
        }
        echo json_encode($result);
        $this->end();
        exit();
    }

    /**
     * 错误信息。
     *
     * @param string $message 错误信息。
     * @param string $url 跳转地址。
     * @param int $second 跳转时间。
     */
    public function error($message = '', $url = '', $second = 3) {
        $this->assign('message', $message);
        $this->assign('url', $url);
        $this->assign('second', $second);
        $script_path = $this->getViewPath();
        $this->_view->display($script_path . "/common/error.php");
        $this->end();
    }

    /**
     * 成功信息。
     *
     * @param string $message 错误信息。
     * @param string $url 跳转地址。
     * @param int $second 跳转时间。
     * @return void
     */
    public function success($message = '', $url = '', $second = 3) {
        $this->assign('message', $message);
        $this->assign('url', $url);
        $this->assign('second', $second);
        $script_path = $this->getViewPath();
        $this->_view->display($script_path . "/common/error.php");
        $this->end();
    }
}
