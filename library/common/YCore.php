<?php
/**
 * 公用类库。
 * @author fingerQin
 * @date 2015-11-13
 */

namespace common;

use finger\Validator;
use services\ConfigService;
use services\DictService;

class YCore
{
    /**
     * 字符串星号处理器。
     * 
     * @param  string $str    被加星处理的字符串。
     * @param  int    $start  星号起始位置。
     * @param  int    $length 星号长度。
     * @return string
     */
    public static function asterisk($str, $start, $length = 0)
    {
        $strLength = mb_strlen($str, 'UTF-8');
        $startStr  = ''; // 头部的字符串。
        $endStr    = ''; // 尾部的字符串。
        $asterisk  = ''; // 星号部分。
        $start     = $start >= 0 ? $start : 0;
        $start     = $start > $strLength ? $strLength : $start;
        $safeLen   = $strLength - $start; // 剩余可以被星号处理的安全长度。
        $length    = ($length <= $safeLen) ? $length : $safeLen;
        $length    = $length <= 0 ? $safeLen : $length;
        if ($start > 0) {
            $startStr = mb_substr($str, 0, $start, 'UTF-8');
        }
        if ($length != $safeLen) {
            $endStr = mb_substr($str, $start + $length, $length, 'UTF-8');;
        }
        $asterisk = str_repeat('*', $length);
        return $startStr . $asterisk . $endStr;
    }

    /**
     * 时间戳格式化。
     *
     * @param  int     $timestamp  时间戳。
     * @param  string  $format     时间输出格式。
     * @return string  $dot        当时间戳 为0的时候返回的值。
     * @return string
     */
    public static function formatTimestamp($timestamp, $format = 'Y-m-d H:i:s', $dot = '-')
    {
        if ($timestamp == 0) {
            return $dot;
        } else {
            return date($format, $timestamp);
        }
    }

    /**
     * 日期格式化。
     *
     * @param  int    $datetime 日期时间。
     * @param  string $format   时间输出格式。
     * @return string $dot      当时间戳 为0的时候返回的值。
     * @return string
     */
    public static function formatDateTime($datetime, $dot = '-')
    {
        if ($datetime == '0000-00-00 00:00:00') {
            return $dot;
        } else {
            return $datetime;
        }
    }

    /**
     * 定义一个PHP set_error_handler 的错误回调函数。
     *
     * @param  int    $errno   错误的级别。
     * @param  string $errstr  错误的信息。
     * @param  string $errfile 发生错误的文件名。
     * @param  int    $errline 错误发生的行号。
     * @return void
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $trace = "PHP Error:{$errno}\nError Message:{$errstr}\nError File:{$errfile}\nError Line:{$errline}";
        self::exception(-1, $trace);
    }

    /**
     * 定义一个PHP register_shutdown_function 回调方法。
     *
     * @return void
     */
    public static function registerShutdownFunction()
    {
        $errInfo = error_get_last();
        if (!empty($errInfo)) {
            $trace = "PHP Error:{$errInfo['type']}\nError Message:{$errInfo['message']}\nError File:{$errInfo['file']}\nError Line:{$errInfo['line']}";
            YCore::log($trace);
            exit;
        }
    }

    /**
     * 从数组中读取一个数组。
     *
     * @param  array  $data         数组。
     * @param  string $name         参数名称。
     * @param  array  $defaultValue 默认值。
     * @return int
     */
    public static function getArray($data, $name, $defaultValue = null)
    {
        if (empty($data) || !is_array($data)) {
            self::exception(STATUS_ERROR, '值为空');
        }
        if (!isset($data[$name])) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} cannot be empty");
            } else if (!is_array($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} of the default value is not a array");
            } else {
                return $defaultValue;
            }
        } else {
            $value = $data[$name];
            if (!is_array($value)) {
                YCore::exception(STATUS_ERROR, "{$name} value is not a array");
            } else {
                return $value;
            }
        }
    }

    /**
     * 从数组中读取一个整型数值。
     *
     * @param  array    $data          数组。
     * @param  string   $name          参数名称。
     * @param  int      $defaultValue  默认值。
     * @return int
     */
    public static function getInt($data, $name, $defaultValue = null)
    {
        if (empty($data) || !is_array($data)) {
            self::exception(STATUS_ERROR, '值为空');
        }
        if (!isset($data[$name])) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} cannot be empty");
            } else if (!Validator::is_integer($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} of the default value is not a integer");
            } else {
                return $defaultValue;
            }
        } else {
            $value = $data[$name];
            if (!Validator::is_integer($value)) {
                YCore::exception(STATUS_ERROR, "{$name} value is not a integer");
            } else {
                return $value;
            }
        }
    }

    /**
     * 从数组中读取一个字符串数值。
     *
     * @param  array   $data         数组。
     * @param  string  $name         参数名称。
     * @param  string  $defaultValue 默认值。
     * @return int
     */
    public static function getString($data, $name, $defaultValue = null)
    {
        if (empty($data) || !is_array($data)) {
            self::exception(STATUS_ERROR, '值为空');
        }
        if (!isset($data[$name])) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} cannot be empty");
            } else {
                return $defaultValue;
            }
        } else {
            return $data[$name];
        }
    }

    /**
     * 从数组中读取一个整型数值。
     *
     * @param  array   $data         数组。
     * @param  string  $name         参数名称。
     * @param  float   $defaultValue 默认值。
     * @return float
     */
    public static function getFloat($data, $name, $defaultValue = null)
    {
        if (empty($data) || !is_array($data)) {
            self::exception(STATUS_ERROR, '值为空');
        }
        if (!isset($data[$name])) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} cannot be empty");
            } else if (!Validator::is_float($defaultValue)) {
                YCore::exception(STATUS_ERROR, "{$name} of the default value is not a float");
            } else {
                return $defaultValue;
            }
        } else {
            $value = $data[$name];
            if (!Validator::is_integer($value)) {
                YCore::exception(STATUS_ERROR, "{$name} value is not a float");
            } else {
                return $value;
            }
        }
    }

    /**
     * 获取系统缓存对象。
     *
     * @return \Redis
     */
    public static function getCache()
    {
        $ok = \Yaf_Registry::has('__system__cache__');
        if ($ok) {
            return \Yaf_Registry::get('__system__cache__');
        } else {
           $system_cache = new \finger\cache\redis\Cache();
           \Yaf_Registry::set('__system__cache__', $system_cache);
           return $system_cache;
        }
    }

    /**
     * 服务降级处理。
     * -- 位置说明：
     * -- register : 注册
     * -- login : 登录
     * -- findpwd : 找回密码
     * -- editpwd : 修改密码
     * -- pay : 支付通道
     * -- sms : 短信通道
     * -- email : 邮件通道
     * -- appraise : 评价通道
     * -- upload : 上传通道
     * -- orderview : 订单查看
     * -- withdrawal : 提现通道
     * -- api : API入口通道
     * -- wechat : 微信入口通道
     * -- all : 整站
     *
     * @param string $location 被降级位置。
     * @return void
     */
    public static function serviceDegradation($location)
    {
        $system_status = self::config('system_status');
        $system_service_level = self::config('system_service_level');
        if (!$system_status) {
            // @todo 如果根据页面不同进行跳转。
            self::exception(STATUS_ERROR, '系统暂时关闭');
        }
        $arr_level = explode(',', $system_service_level);
        foreach ($arr_level as $key => $level) {
            $arr_level[$key] = trim($level);
        }
        switch ($location) {
            case 'all':
                $errCode      = 5001001;
                $server_level = 8192;
                $server_msg   = '服务器维护中';
                break;
            case 'register':
                $errCode      = 5001002;
                $server_level = 1;
                $server_msg   = '注册功能已关闭';
                break;
            case 'login':
                $errCode      = 5001003;
                $server_level = 2;
                $server_msg   = '登录功能已关闭';
                break;
            case 'findpwd':
                $errCode      = 5001004;
                $server_level = 4;
                $server_msg   = '找回密码功能已关闭';
                break;
            case 'editpwd':
                $errCode      = 5001005;
                $server_level = 8;
                $server_msg   = '密码修改功能已关闭';
                break;
            case 'pay':
                $errCode      = 5001006;
                $server_level = 16;
                $server_msg   = '支付功能已关闭';
                break;
            case 'sms':
                $errCode      = 5001007;
                $server_level = 32;
                $server_msg   = '短信功能已关闭';
                break;
            case 'email':
                $errCode      = 5001008;
                $server_level = 64;
                $server_msg   = '邮件功能已关闭';
                break;
            case 'appraise':
                $errCode      = 5001009;
                $server_level = 128;
                $server_msg   = '评价功能已关闭';
                break;
            case 'upload':
                $errCode      = 5001010;
                $server_level = 256;
                $server_msg   = '上传功能已关闭';
                break;
            case 'orderview':
                $errCode      = 5001011;
                $server_level = 512;
                $server_msg   = '订单查看功能已关闭';
                break;
            case 'withdrawal':
                $errCode      = 5001012;
                $server_level = 1024;
                $server_msg   = '提现功能已关闭';
                break;
            case 'api':
                $errCode      = 5001013;
                $server_level = 2048;
                $server_msg   = 'API接口功能已关闭';
                break;
            case 'wechat':
                $errCode      = 5001014;
                $server_level = 4096;
                $server_msg   = '微信应用已关闭';
                break;
            default :
                $errCode      = 5001015;
                $server_level = 0;
                break;
        }
        if (in_array($server_level, $arr_level)) {
            YCore::exception($errCode, $server_msg);
        }
    }

    /**
     * 打印变量。
     *
     * @param mixed $data
     */
    public static function print_r($data) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    /**
     * 获取系统字典数据。
     *
     * @param  string $dict_type_code 字典类型编码。
     * @param  string $dict_code      字典编码。
     * @return array
     */
    public static function dict($dict_type_code, $dict_code = '')
    {
        return DictService::getSystemDict($dict_type_code, $dict_code);
    }

    /**
     * 获取系统配置表里面的配置。
     *
     * @param string $cname         配置名称。
     * @param string $defaultValue 如果取不到时的默认值。注意数据类型。
     * @param bool   $isReadDB      是否直接从数据库读取。true - 是、false - 否。从缓存读取。
     * @return string
     */
    public static function config($cname, $defaultValue = null, $isReadDB = false)
    {
        if ($isReadDB) {
            $configs = ConfigService::directReadDbConfig();
        } else {
            $configs = ConfigService::getAllConfig();
        }
        if (!isset($configs[$cname])) {
            if (is_null($defaultValue)) {
                YCore::exception(STATUS_SERVER_ERROR, "系统配置（{$cname}）未设置");
            } else {
                return $defaultValue;
            }
        } else {
            return $configs[$cname];
        }
    }

    /**
     * 设置系统配置表的值。
     * -- 1、主要用于一些需要程序运行后记录值。
     *
     * @param string $cname 配置name。
     * @param string $value 配置值。
     * @return bool
     */
    public static function setconfig($cname, $value)
    {
        return ConfigService::updateConfigValue($cname, $value);
    }

    /**
     * Yaf框架内所有位置产生的日志都由此方法写入。
     *
     * @param  string  $logContent      日志内容。
     * @param  string  $logTime         日志产生时间。格式：2017-05-27 12:00:00
     * @param  int     $errcode         错误编号。
     * @param  string  $logDir          日志目录。如：bank
     * @param  string  $logFilename     日志文件名称。如：bind。生成文件的时候会在 bind 后面接上日期。如:bind-20171121.log
     * 
     * @return void
     */
    public static function log($logContent, $logTime = '', $errcode = 0, $logDir = '', $logFilename = '')
    {
        $model   = new \models\ErrorLog();
        $logTime = strlen($logTime) > 0 ? $logTime : date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $logfile = date('Ymd', $_SERVER['REQUEST_TIME']);
        if (strlen($logDir) > 0 && strlen($logFilename) > 0) {
            $logDir   = trim($logDir, '/');
            $logPath  = APP_PATH . "/logs/" . $logDir;
            YDir::dir_create($logPath);
            $logPath .= "/{$logFilename}-{$logfile}.log";
        } else {
            $logPath = APP_PATH . '/logs/errors/' . $logfile . '.log';
        }
        file_put_contents($logPath, "ErrorTime:{$logTime}\nErrorCode:{$errcode}\nErrorLog:{$logContent}\n\n", FILE_APPEND);
        $model->addLog($logContent, $logfile, $errcode);
    }

    /**
     * 将PHP变量到处为文件内容
     *
     * @param mixed $var
     * @return string
     */
    public static function export($var)
    {
        return "<?php\nreturn " . var_export($var, true) . ";";
    }

    /**
     * 获取当前时间戳。
     *
     * @param bool $real_time 是否实时的日期。如果取入口的微秒，则会出现业务处理超长时间，依然是业务处理之前的时间。
     * @return int
     */
    public static function getTimestamp($real_time = false)
    {
        if ($real_time === false) {
            return time();
        } else {
            list($usec, $sec) = explode(' ', MICROTIME);
            return $sec;
        }
    }

    /**
     * 根据两点间的经纬度计算距离
     * -- 1、纬度最大值为90度，经度最大值为180度。
     * -- 2、只要其中一个值为-1则返回0.这是特殊约定的业务逻辑。
     *
     * @param float $lat 纬度值。
     * @param float $lng 经度值。
     * @param float $lat2 纬度值2。
     * @param float $lng2 经度值2。
     * @return int
     */
    public static function distance($lat1, $lng1, $lat2, $lng2)
    {
        if ($lat1 == - 1 || $lng1 == - 1 || $lat2 == - 1 || $lng2 == - 1) {
            return 0;
        }
        $earthRadius = 6371000; // approximate radius of earth in meters
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude  = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }

    /**
     * MongoCursor对象转数组。
     *
     * @param MongoCursor $cursor
     * @return array
     */
    public static function mongoToArray($cursor)
    {
        $data = [];
        foreach ($cursor as $document) {
            $data[] = $document;
        }
        return $data;
    }

    /**
     * 数组转换为树。
     *
     * @param  array  $sourceArr    源数组。
     * @param  string $key          数组主键名称。
     * @param  string $parentKey    数组父id键名称。
     * @param  string $childrenKey  生成的子树键名称。
     * @return array
     */
    public static function arrtyToTree($sourceArr, $key, $parentKey, $childrenKey)
    {
        $tempSrcArr = [];
        foreach ($sourceArr as $v) {
            $tempSrcArr[$v[$key]] = $v;
        }
        $i = 0;
        $count = count($sourceArr);
        for($i = ($count - 1); $i >= 0; $i --) {
            if (isset($tempSrcArr[$sourceArr[$i][$parentKey]])) {
                $tArr = array_pop($tempSrcArr);
                $tempSrcArr[$tArr[$parentKey]][$childrenKey] = (isset($tempSrcArr[$tArr[$parentKey]][$childrenKey]) && is_array($tempSrcArr[$tArr[$parentKey]][$childrenKey])) ? $tempSrcArr[$tArr[$parentKey]][$childrenKey] : [];
                array_push($tempSrcArr[$tArr[$parentKey]][$childrenKey], $tArr);
            }
        }
        // 最外层关联索引转换为数字索引，这样在json转换的时候是list,而非对象。
        $_items = [];
        foreach ($tempSrcArr as $_temp_item) {
            $_items[] = $_temp_item;
        }
        return $_items;
    }

    /**
     * 读取配置文件。
     *
     * @param  string $key 配置名。
     * @param  string $val 当值不存在返回此值。
     * @return mixed
     */
    public static function appconfig($key, $val = null)
    {
        $config = \Yaf_Registry::get("config");
        $cval   = $config->get($key);
        if (is_string($cval)) {
            return $cval;
        } else if (is_null($cval) === false) {
            return $cval->toArray();
        } else {
            return $val;
        }
    }

    /**
     * 抛出异常。
     * 
     * @param  int     $errCode             错误编号。
     * @param  string  $errMsg              错误信息。
     * @param  string  $classNameAndMethod  出错位置执行的类与方法。当使用 try cacth 捕获异常时将捕获的异常信息传入。
     * @param  string  $args                出错位置传入方法的参数。当使用 try cacth 捕获异常时将捕获的异常信息传入。
     * @throws \finger\ServiceException
     */
    public static function exception($errCode, $errMsg, $classNameAndMethod = '', $args = [])
    {
        if (strlen($classNameAndMethod) === 0) {
            // debug_backtrace() 返回整个堆栈调用信息。
            // 堆栈里面的第二个数组返回的是调用 YCore::exception() 方法所在的类与方法相关信息。
            $result             = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $classNameAndMethod = $result[1]['class'] . $result[1]['type'] . $result[1]['function'];
            $args               = $result[1]['args'];
        }
        throw new \finger\ServiceException($errMsg, $errCode, $classNameAndMethod, $args);
    }

    /**
     * 返回经addslashes处理过的字符串或数组
     *
     * @param $string 需要处理的字符串或数组
     * @return mixed
     */
    public static function new_addslashes($string)
    {
        if (!is_array($string)) {
            return addslashes($string);
        }
        foreach ($string as $key => $val) {
            $string[$key] = self::new_addslashes($val);
        }
        return $string;
    }

    /**
     * 返回经stripslashes处理过的字符串或数组
     *
     * @param $string 需要处理的字符串或数组
     * @return mixed
     */
    public static function new_stripslashes($string)
    {
        if (!is_array($string)) {
            return stripslashes($string);
        }
        foreach ($string as $key => $val) {
            $string[$key] = self::new_stripslashes($val);
        }
        return $string;
    }

    /**
     * 返回经htmlspecialchars处理过的字符串或数组
     *
     * @param $obj 需要处理的字符串或数组
     * @return mixed
     */
    public static function new_html_special_chars($string)
    {
        $encoding = 'utf-8';
        if (!is_array($string)) {
            return htmlspecialchars($string, ENT_QUOTES, $encoding);
        }
        foreach ($string as $key => $val) {
            $string[$key] = self::new_html_special_chars($val);
        }
        return $string;
    }

    public static function new_html_entity_decode($string)
    {
        $encoding = 'utf-8';
        return html_entity_decode($string, ENT_QUOTES, $encoding);
    }

    public static function new_htmlentities($string)
    {
        $encoding = 'utf-8';
        return htmlentities($string, ENT_QUOTES, $encoding);
    }

    /**
     * 过滤ASCII码从0-28的控制字符
     *
     * @return String
     */
    public static function trim_unsafe_control_chars($str)
    {
        $rule = '/[' . chr(1) . '-' . chr(8) . chr(11) . '-' . chr(12) . chr(14) . '-' . chr(31) . ']*/';
        return str_replace(chr(0), '', preg_replace($rule, '', $str));
    }

    /**
     * 格式化文本域内容
     *
     * @param $string 文本域内容
     * @return string
     */
    public static function trim_textarea($string)
    {
        $string = nl2br(str_replace(' ', '&nbsp;', $string));
        return $string;
    }

    /**
     * 字符截取支持UTF8。
     *
     * @param $string
     * @param $length
     * @param $dot
     * @return string
     */
    public static function str_cut($string, $length, $dot = '...')
    {
        $strlen = strlen($string);
        if ($strlen <= $length) {
            return $string;
        }
        $string = str_replace([
            ' ',
            '&nbsp;',
            '&amp;',
            '&quot;',
            '&#039;',
            '&ldquo;',
            '&rdquo;',
            '&mdash;',
            '&lt;',
            '&gt;',
            '&middot;',
            '&hellip;'
        ], [
            '∵',
            ' ',
            '&',
            '"',
            "'",
            '“',
            '”',
            '—',
            '<',
            '>',
            '·',
            '…'
        ], $string);
        $strcut = '';
        $length = intval($length - strlen($dot) - $length / 3);
        $n = $tn = $noc = 0;
        while ($n < strlen($string) ) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }
        $strcut = substr($string, 0, $n);
        $strcut = str_replace([
            '∵',
            '&',
            '"',
            "'",
            '“',
            '”',
            '—',
            '<',
            '>',
            '·',
            '…'
        ], [
            ' ',
            '&amp;',
            '&quot;',
            '&#039;',
            '&ldquo;',
            '&rdquo;',
            '&mdash;',
            '&lt;',
            '&gt;',
            '&middot;',
            '&hellip;'
        ], $strcut);
        return $strcut . $dot;
    }

    public static function get_cost_time()
    {
        $microtime = microtime(TRUE);
        return $microtime - LARAVEL_START;
    }

    /**
     * 产生随机字符串
     *
     * @param  int      $length     输出长度
     * @param  string   $chars      可选的，默认为 0123456789
     * 
     * @return string 字符串
     */
    public static function random($length, $chars = '0123456789')
    {
        $hash = '';
        $max  = strlen($chars) - 1;
        for($i = 0; $i < $length; $i ++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 生成随机字符串
     *
     * @param string $lenth 长度
     * @return string 字符串
     */
    public static function create_randomstr($lenth = 6)
    {
        return self::random($lenth, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
    }

    /**
     * 转换字节数为其他单位
     *
     * @param  string $filesize 字节大小
     * @return string 返回大小
     */
    public static function sizecount($filesize)
    {
        if ($filesize >= 1073741824) {
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        } elseif ($filesize >= 1048576) {
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        } elseif ($filesize >= 1024) {
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        } else {
            $filesize = $filesize . ' Bytes';
        }
        return $filesize;
    }

    /**
     * 字符串加密、解密函数
     *
     * @param  string $txt          字符串
     * @param  string $operation    ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
     * @param  string $key          密钥：数字、字母、下划线
     * @param  string $expiry       过期时间
     * @return string
     */
    public static function sys_auth($string, $operation = 'ENCODE', $key = '', $expiry = 0)
    {
        $key_length    = 4;
        $key           = md5($key != '' ? $key : self::appconfig('authkey'));
        $fixedkey      = md5($key);
        $egiskeys      = md5(substr($fixedkey, 16, 16));
        $runtokey      = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), - $key_length) : substr($string, 0, $key_length)) : '';
        $keys          = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
        $string        = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));
        $i             = 0;
        $result        = '';
        $string_length = strlen($string);
        for ($i = 0; $i < $string_length; $i ++) {
            $result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
        }
        if ($operation == 'ENCODE') {
            return $runtokey . str_replace('=', '', base64_encode($result));
        } else {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $egiskeys), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        }
    }

    /**
     * 取得文件扩展
     *
     * @param $filename 文件名
     * @return 扩展名
     */
    public static function fileext($filename)
    {
        return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
    }

    /**
     * 文件下载
     *
     * @param $filepath 文件路径
     * @param $filename 文件名称
     * @return void
     */
    public static function file_down($filepath, $filename = '')
    {
        if (!$filename) {
            $filename = basename($filepath);
        }
        if (self::is_ie()) {
            $filename = rawurlencode($filename);
        }
        $filetype = self::fileext($filename);
        $filesize = sprintf("%u", filesize($filepath));
        if (ob_get_length() !== false) {
            @ob_end_clean();
        }
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: none');
        header('Content-type: ' . $filetype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-length: ' . $filesize);
        readfile($filepath);
        exit();
    }

    /**
     * 对数据进行编码转换
     *
     * @param  array/string $data   数组
     * @param  string       $input  需要转换的编码
     * @param  string       $output 转换后的编码
     * @return string|array
     */
    public static function array_iconv($data, $input = 'gbk', $output = 'utf-8')
    {
        if (!is_array($data)) {
            return iconv($input, $output, $data);
        } else {
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $data[$key] = self::array_iconv($val, $input, $output);
                } else {
                    $data[$key] = iconv($input, $output, $val);
                }
            }
            return $data;
        }
    }

    /**
     *
     * 获取远程内容
     *
     * @param  string $url     接口url地址
     * @param  int    $timeout 超时时间
     * @return string
     */
    public static function pc_file_get_contents($url, $timeout = 30)
    {
        $stream = stream_context_create([
            'http' => [
                'timeout' => $timeout
            ]
        ]);
        return @file_get_contents($url, 0, $stream);
    }

    /**
     * 获取请求ip
     *
     * @return string ip地址
     */
    public static function ip()
    {
        $ip = '127.0.0.1';
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')){
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
    }

    /**
     * IE浏览器判断
     * @return boolean
     */
    public static function is_ie()
    {
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if ((strpos($useragent, 'opera') !== false) || (strpos($useragent, 'konqueror') !== false)) {
            return false;
        }
        if (strpos($useragent, 'msie ') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 多久之前
     *
     * @param  string  $datetime 时间：2017-03-22 08:08:08
     * @return string
     */
    public static function howLongAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $seconds   = $_SERVER['REQUEST_TIME'];
        $time      = date('Y', $seconds) - date('Y', $timestamp);
        if ($time > 0) {
            if ($time == 1) {
                return '去年';
            } else {
                return $time . '年前';
            }
        }
        $time = date('m', $seconds) - date('m', $timestamp);
        if ($time > 0) {
            if ($time == 1) {
                return '上月';
            } else {
                return $time . '个月前';
            }
        }
        $time = date('d', $seconds) - date('d', $timestamp);
        if ($time > 0) {
            if ($time == 1) {
                return '昨天';
            } elseif ($time == 2) {
                return '前天';
            } else {
                return $time . '天前';
            }
        }
        $time = date('H', $seconds) - date('H', $timestamp);
        if ($time >= 1) {
            return $time . '小时前';
        }
        $time = date('i', $seconds) - date('i', $timestamp);
        if ($time >= 1) {
            return $time . '分钟前';
        }
        $time = date('s', $seconds) - date('s', $timestamp);
        return $time . '秒前';
    }

    /**
     * 根据生日中的月份和日期来计算所属星座*
     *
     * @param  int $birth_month
     * @param  int $birth_date
     * @return string
     */
    public static function get_constellation($birth_month, $birth_date)
    {
        // 判断的时候，为避免出现1和true的疑惑，或是判断语句始终为真的问题，这里统一处理成字符串形式
        $birth_month = strval($birth_month);
        $constellation_name = [
            '水瓶座',
            '双鱼座',
            '白羊座',
            '金牛座',
            '双子座',
            '巨蟹座',
            '狮子座',
            '处女座',
            '天秤座',
            '天蝎座',
            '射手座',
            '摩羯座'
        ];
        if ($birth_date <= 22) {
            if ('1' !== $birth_month) {
                $constellation = $constellation_name[$birth_month - 2];
            } else {
                $constellation = $constellation_name[11];
            }
        } else {
            $constellation = $constellation_name[$birth_month - 1];
        }
        return $constellation;
    }

    /**
     * 根据生日中的年份来计算所属生肖
     *
     * @param  int $birth_year
     * @param  int $format 格式化形式。1-十二地支、2-十二生肖。
     * @return string
     */
    public static function get_animal($birth_year, $format = 1)
    {
        // 1900年是子鼠年
        if ($format == '2') {
            $animal = [
                '子鼠',
                '丑牛',
                '寅虎',
                '卯兔',
                '辰龙',
                '巳蛇',
                '午马',
                '未羊',
                '申猴',
                '酉鸡',
                '戌狗',
                '亥猪'
            ];
        } elseif ($format == '1') {
            $animal = [
                '鼠',
                '牛',
                '虎',
                '兔',
                '龙',
                '蛇',
                '马',
                '羊',
                '猴',
                '鸡',
                '狗',
                '猪'
            ];
        }
        $my_animal = ($birth_year - 1900) % 12;
        return $animal[$my_animal];
    }

    /**
     * 递归计算一个数值。
     * @param  int $a 数值。
     * @return int
     */
    public static function factorial($a)
    {
        if ($a > 1) {
            $r = $a * self::factorial($a - 1);
        } else {
            $r = $a;
        }
        return $r;
    }

    /**
     * xss过滤函数
     *
     * @param string $string
     * @return string
     */
    public static function remove_xss($string)
    {
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);
        $parm1 = [
            'javascript',
            'vbscript',
            'expression',
            'applet',
            'meta',
            'xml',
            'blink',
            'link',
            'script',
            'embed',
            'object',
            'iframe',
            'frame',
            'frameset',
            'ilayer',
            'layer',
            'bgsound',
            'title',
            'base'
        ];
        $parm2 = [
            'onabort',
            'onactivate',
            'onafterprint',
            'onafterupdate',
            'onbeforeactivate',
            'onbeforecopy',
            'onbeforecut',
            'onbeforedeactivate',
            'onbeforeeditfocus',
            'onbeforepaste',
            'onbeforeprint',
            'onbeforeunload',
            'onbeforeupdate',
            'onblur',
            'onbounce',
            'oncellchange',
            'onchange',
            'onclick',
            'oncontextmenu',
            'oncontrolselect',
            'oncopy',
            'oncut',
            'ondataavailable',
            'ondatasetchanged',
            'ondatasetcomplete',
            'ondblclick',
            'ondeactivate',
            'ondrag',
            'ondragend',
            'ondragenter',
            'ondragleave',
            'ondragover',
            'ondragstart',
            'ondrop',
            'onerror',
            'onerrorupdate',
            'onfilterchange',
            'onfinish',
            'onfocus',
            'onfocusin',
            'onfocusout',
            'onhelp',
            'onkeydown',
            'onkeypress',
            'onkeyup',
            'onlayoutcomplete',
            'onload',
            'onlosecapture',
            'onmousedown',
            'onmouseenter',
            'onmouseleave',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onmousewheel',
            'onmove',
            'onmoveend',
            'onmovestart',
            'onpaste',
            'onpropertychange',
            'onreadystatechange',
            'onreset',
            'onresize',
            'onresizeend',
            'onresizestart',
            'onrowenter',
            'onrowexit',
            'onrowsdelete',
            'onrowsinserted',
            'onscroll',
            'onselect',
            'onselectionchange',
            'onselectstart',
            'onstart',
            'onstop',
            'onsubmit',
            'onunload'
        ];
        $parm  = array_merge($parm1, $parm2);
        for($i = 0; $i < sizeof($parm); $i ++) {
            $pattern = '/';
            for($j = 0; $j < strlen($parm[$i]); $j ++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                    $pattern .= '|(&#0([9][10][13]);?)?';
                    $pattern .= ')?';
                }
                $pattern .= $parm[$i][$j];
            }
            $pattern .= '/i';
            $string = preg_replace($pattern, '', $string);
        }
        return $string;
    }

    /**
     * 转义 javascript 代码标记
     *
     * @param string $str
     * @return mixed
     */
    public static function trim_script($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = self::trim_script($val);
            }
        } else {
            $str = preg_replace('/\<([\/]?)script([^\>]*?)\>/si', '&lt;\\1script\\2&gt;', $str);
            $str = preg_replace('/\<([\/]?)iframe([^\>]*?)\>/si', '&lt;\\1iframe\\2&gt;', $str);
            $str = preg_replace('/\<([\/]?)frame([^\>]*?)\>/si', '&lt;\\1frame\\2&gt;', $str);
            $str = str_replace('javascript:', 'javascript：', $str);
        }
        return $str;
    }

    /**
     * 安全过滤函数
     *
     * @param string $string
     * @return string
     */
    public static function safe_replace($string)
    {
        $string = str_replace('%20', '', $string);
        $string = str_replace('%27', '', $string);
        $string = str_replace('%2527', '', $string);
        $string = str_replace('*', '', $string);
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace("'", '', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace(';', '', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        $string = str_replace("{", '', $string);
        $string = str_replace('}', '', $string);
        $string = str_replace('\\', '', $string);
        return $string;
    }

    /**
     * 将文本格式成适合js输出的字符串
     *
     * @param string $string 需要处理的字符串
     * @param intval $isjs 是否执行字符串格式化，默认为执行
     * @return string 处理后的字符串
     */
    public static function format_js($string, $isjs = 1)
    {
        $string = addslashes(str_replace([
            "\r",
            "\n",
            "\t"
        ], [
            '',
            '',
            ''
        ], $string));
        return $isjs ? 'document.write("' . $string . '");' : $string;
    }

    /**
     * 移除两个数组中相同的元素并返回不同部分的数组。
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function array_remove_equal(array $array1, array $array2)
    {
        $diff_array = [];
        foreach ($array1 as $val) {
            if (!in_array($val, $array2)) {
                $diff_array[] = $val;
            }
        }
        foreach ($array2 as $val) {
            if (!in_array($val, $array1)) {
                $diff_array[] = $val;
            }
        }
        return $diff_array;
    }

    /**
     * 获取身份证号对应的性别信息。
     *
     * @param  string $idCardNo 身份证号。
     * @return void
     */
    public static function getIdCardNoSex($idCardNo)
    {
        if (strlen($idCardNo) === 0) {
            return User::SEX_UNKNOWN;
        }
        $sex = substr($idCardNo, 16, 1);
        return (($sex%2) == 1) ? User::SEX_MALE : User::SEX_FEMAIL;
    }

    /**
     * 获取身份证号对应的生日信息。
     *
     * @param  string  $idCardNo  身份证号。
     * @return string
     */
    public static function getIdCardNoBirthday($idCardNo)
    {
        if (strlen($idCardNo) === 0) {
            return null;
        }
        $year  = substr($idCardNo, 6, 4);
        $month = substr($idCardNo, 10, 2);
        $day   = substr($idCardNo, 12, 2);
        return "{$year}-{$month}-{$day}";
    }

    /**
     * 随机指定个数的整数范围值。
     * 
     * --1、范围段数值个数小于等于要取的个数的10倍，则直接使用shuffle方式获取。
     * --2、范围段数值个数大于要取的个数的10倍，则每个数值都随机产生。并去重。
     * --3、以上两点一是为了性能，二是为了能避免无效的随机值。
     *
     * @param  int  $min    范围最小值(含)。
     * @param  int  $max    范围小大值(含)。
     * @param  int  $count  要取的值个数。
     * 
     * @return array
     */
    public static function randomIntegerScope($min, $max, $count = 20)
    {
        $validCount = ($max - $min) + 1; // 包含边界值。所以，要加1。
        if ($validCount <= $count * 10) {
            $scopeVal = array_fill($min, $validCount, 0);
            $keys = array_keys($scopeVal);
            shuffle($keys);
            return array_slice($keys, 0, $count);
        } else {
            $randVals = [];
            while(true) {
                $randVal = mt_rand($min, $max);
                if (!in_array($randVal, $randVals)) {
                    $randVals[] = $randVal;
                    if (count($randVals) == $count) {
                        break;
                    }
                }
            }
            return $randVals;
        }
    }
}
