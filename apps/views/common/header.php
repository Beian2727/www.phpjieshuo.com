<?php
use common\YCore;
use common\YUrl;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1, user-scalable=no">
<title>用户中心</title>
<link title="" href="<?php echo YUrl::assets('css', '/frontend/style.css'); ?>" rel="stylesheet" type="text/css"  />
<link title="blue" href="<?php echo YUrl::assets('css', '/frontend/dermadefault.css'); ?>" rel="stylesheet" type="text/css"/>
<link href="<?php echo YUrl::assets('css', '/frontend/templatecss.css'); ?>" rel="stylesheet" title="" type="text/css" />
<script src="<?php echo YUrl::assets('js', '/frontend/jquery-1.11.1.min.js'); ?>" type="text/javascript"></script>

<link href="<?php echo YUrl::assets('js', '/layui/css/layui.css'); ?>" rel="stylesheet" type="text/css" />
<script src="<?php echo YUrl::assets('js', '/layui/layui.js'); ?>" type="text/javascript"></script>
<link href="<?php echo YUrl::assets('css', '/frontend/global.css'); ?>" rel="stylesheet" type="text/css" />

<!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
<!--[if lt IE 9]>
  <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
  <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
<![endif]--> 

</head>

<body>
<div class="layui-header header header-doc">
  <div class="layui-main">
    <ul class="layui-nav">
      <li class="layui-nav-item <?php if($ctrlName == 'Index'): ?>layui-this<?php endif; ?>">
        <a href="<?php echo YUrl::createFrontendUrl('Index', 'index'); ?>">文档</a> 
      </li>
      <li class="layui-nav-item <?php if($ctrlName == 'Question'): ?>layui-this<?php endif; ?>">
        <a href="<?php echo YUrl::createFrontendUrl('Question', 'index'); ?>">答题</a> 
      </li>
    </ul>
    <span class="layui-nav-bar"></span>
    <?php
      if (isset($is_login) && $is_login) {
        require_once (dirname(__DIR__) . '/common/userinfo.php');
      }
    ?>
  </div>
  
</div>

<div class="down-main">