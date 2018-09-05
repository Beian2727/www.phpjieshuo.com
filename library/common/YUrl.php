<?php
/**
 * URL相关封装。
 * @author fingerQin
 * @date 2016-04-05
 */

namespace common;

class YUrl
{
    /**
     * 获取当前去除分页符的URL。
     * -- 1、用于分页使用。
     *
     * @param  array  $params  参数。
     * @return string
     */
    public static function getCurrentTrimPageUrl(array $params = [])
    {
        $sysProtocal = 'https://';
        if (isset($_SERVER['SERVER_PORT'])) {
            if ($_SERVER['SERVER_PORT'] == '80') {
                $sysProtocal = 'http://';
            } else if ($_SERVER['SERVER_PORT'] == '443') {
                $sysProtocal = 'https://';
            }
        }
        $phpSelf   = $_SERVER['PHP_SELF'] ? YCore::safe_replace($_SERVER['PHP_SELF']) : self::safe_replace($_SERVER['SCRIPT_NAME']);
        $pathInfo  = isset($_SERVER['PATH_INFO']) ? YCore::safe_replace($_SERVER['PATH_INFO']) : '';
        $relateUrl = isset($_SERVER['REQUEST_URI']) ? YCore::safe_replace($_SERVER['REQUEST_URI']) : $phpSelf . (isset($_SERVER['QUERY_STRING']) ? '?' . YCore::safe_replace($_SERVER['QUERY_STRING']) : $pathInfo);
        $pager     = YCore::appconfig('pager');
        $filterGet = [];
        foreach ($_GET as $k => $v) {
            if ($k != $pager) {
                $filterGet[$k] = $v;
            }
        }
        $params = array_merge($filterGet, $params);
        $query  = '';
        if ($params) {
            $query .= http_build_query($params);
        }
        $url       = $sysProtocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relateUrl;
        $urlData   = explode('?', $url);
        $filterUrl = '';
        if ($params) {
            $filterUrl = "{$urlData[0]}?{$query}";
        } else {
            $filterUrl = $urlData[0];
        }
        return $filterUrl;
    }

    /**
     * 创建一个用户中心的URL。
     *
     * @param  string  $controllerName  控制器名称。
     * @param  string  $actionName      操作名称。
     * @param  array   $params          参数。
     * @return string
     */
    public static function createAccountUrl($controllerName, $actionName, array $params = [])
    {
        $accountDomainName = self::getDomainName();
        return self::createPageUrl($accountDomainName, 'User', $controllerName, $actionName, $params);
    }

    /**
     * 创建一个管理后台的URL。
     *
     * @param  string  $controllerName  控制器名称。
     * @param  string  $actionName      操作名称。
     * @param  array   $params          参数。
     * @return string
     */
    public static function createBackendUrl($controllerName, $actionName, array $params = [])
    {
        $backendDomainName = self::getDomainName();
        return self::createPageUrl($backendDomainName, 'Backend', $controllerName, $actionName, $params);
    }

    /**
     * 创建一个前端页面的URL。
     *
     * @param  string  $controllerName  控制器名称。
     * @param  string  $actionName      操作名称。
     * @param  array   $params          参数。
     * @return string
     */
    public static function createFrontendUrl($controllerName, $actionName, array $params = [])
    {
        $frontendDomainName = self::getDomainName();
        return self::createPageUrl($frontendDomainName, 'Index', $controllerName, $actionName, $params);
    }

    /**
     * 创建一个页面URL。
     *
     * @param  string  $domainName      域名。
     * @param  string  $moduleName      模块名称。
     * @param  string  $controllerName  控制器名称。
     * @param  string  $actionName      操作名称。
     * @param  array   $params          参数。
     * @return string
     */
    public static function createPageUrl($domainName, $moduleName = '', $controllerName = '', $actionName = '', array $params = []) 
    {
        $domainName = self::getDomainName();
        $domainName = trim($domainName, '/');
        $query      = '';
        if (strlen($moduleName) > 0) {
            $query .= "{$moduleName}/";
        }
        if (strlen($controllerName) === 0) {
            $query .= "Index/";
        } else {
            $query .= "{$controllerName}/";
        }
        if (strlen($actionName) === 0) {
            YCore::exception(- 1, 'action_name error');
        }
        $query .= $actionName;
        if ($params) {
            $query .= "?" . http_build_query($params);
        }
        return "{$domainName}/{$query}";
    }

    /**
     * 获取上传文件的绝对地址。
     *
     * @param  string  $fileRelativePath 相对路径。
     * @return string
     */
    public static function filePath($fileRelativePath)
    {
        if (strlen($fileRelativePath) === 0) {
            return '';
        } else {
            $filesUrl = self::getFilesDomainName();
            $filesUrl = trim($filesUrl, '/');
            $fileRelativePath = trim($fileRelativePath, '/');
            return $filesUrl . '/' . $fileRelativePath;
        }
    }

    /**
     * 获取文件服务器域名。
     *
     * @return string
     */
    public static function getFilesDomainName()
    {
        $uploadDriver = \common\YCore::appconfig('upload.driver');
        if (\strtolower($uploadDriver) == 'oss') {
            $ossEndPoint = \common\YCore::appconfig('upload.oss.endpoint');
            return 'http://' . $ossEndPoint;
        } else {
            return self::getDomainName();
        }
    }

    /**
     * 获取当前页面完整URL地址
     */
    public static function getUrl()
    {
        $sysProtocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $phpSelf     = $_SERVER['PHP_SELF'] ? YCore::safe_replace($_SERVER['PHP_SELF']) : YCore::safe_replace($_SERVER['SCRIPT_NAME']);
        $pathInfo    = isset($_SERVER['PATH_INFO']) ? YCore::safe_replace($_SERVER['PATH_INFO']) : '';
        $relateUrl   = isset($_SERVER['REQUEST_URI']) ? YCore::safe_replace($_SERVER['REQUEST_URI']) : $phpSelf . (isset($_SERVER['QUERY_STRING']) ? '?' . YCore::safe_replace($_SERVER['QUERY_STRING']) : $pathInfo);
        return $sysProtocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relateUrl;
    }

    /**
     * 获取当前域名。
     *
     * @param  bool $isFullFormat 是否完整格式。完整格式是带 http:// 或 https://
     * @return string
     */
    public static function getDomainName($isFullFormat = true)
    {
        $sysProtocal = '';
        if ($isFullFormat) {
            $sysProtocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        }
        return $sysProtocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    }

    /**
     * 获取静态资源URL。
     *
     * @param  string $type             css、js、image
     * @param  string $fileRelativePath 资源相对路径。如：/jquery/plugins/cookie.js
     * @return string
     */
    public static function assets($type, $fileRelativePath)
    {
        $staticsUrl = self::getDomainName();
        $staticsUrl = trim($staticsUrl, '/');
        $staticsUrl = $staticsUrl . "/statics";
        switch ($type) {
            case 'js' :
                $staticsUrl .= '/js/';
                break;
            case 'css' :
                $staticsUrl .= '/css/';
                break;
            case 'image' :
                $staticsUrl .= '/images/';
                break;
            default :
                $staticsUrl .= "/{$type}/";
                break;
        }
        $fileRelativePath = trim($fileRelativePath, '/');
        return $staticsUrl . $fileRelativePath;
    }
}