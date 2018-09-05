<?php
use common\YCore;
use common\YUrl;
?>
<div class="left-main left-full">
    <div class="subNavBox">

    <div class="sBox">
      <div class="subNav sublist-down"><span class="title-icon glyphicon glyphicon-chevron-down"></span><span class="sublist-title">答题管理</span></div>
      <ul class="navContent" style="display:block">
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />我要答题</div>
          <a href="<?php echo YUrl::createFrontendUrl('Question', 'startDo'); ?>"><span class="sublist-icon glyphicon glyphicon-user"></span><span class="sub-title">我要答题</span></a>
        </li>
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />答题记录</div>
          <a href="<?php echo YUrl::createFrontendUrl('Question', 'record'); ?>"><span class="sublist-icon glyphicon glyphicon-envelope"></span><span class="sub-title">答题记录</span></a>
        </li>
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />答题统计</div>
          <a href="<?php echo YUrl::createFrontendUrl('Question', 'stats'); ?>"><span class="sublist-icon glyphicon glyphicon-bullhorn"></span><span class="sub-title">答题统计</span></a>
        </li>
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />收藏列表</div>
          <a href="<?php echo YUrl::createFrontendUrl('Question', 'favorites'); ?>"><span class="sublist-icon glyphicon glyphicon-bullhorn"></span><span class="sub-title">题目收藏</span></a>
        </li>
      </ul>
    </div>

    <div class="sBox">
      <div class="subNav sublist-down"><span class="title-icon glyphicon glyphicon-chevron-down"></span><span class="sublist-title">技术资料</span></div>
      <ul class="navContent" style="display:block">
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />资料查看</div>
          <a href="<?php echo YUrl::createFrontendUrl('Doc', 'category'); ?>"><span class="sublist-icon glyphicon glyphicon-user"></span><span class="sub-title">资料查看</span></a>
        </li>
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />收藏列表</div>
          <a href="<?php echo YUrl::createFrontendUrl('Doc', 'favorites'); ?>"><span class="sublist-icon glyphicon glyphicon-envelope"></span><span class="sub-title">收藏列表</span></a>
        </li>
        <li>
          <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />资料搜索</div>
          <a href="<?php echo YUrl::createFrontendUrl('Doc', 'search'); ?>"><span class="sublist-icon glyphicon glyphicon-bullhorn"></span><span class="sub-title">资料搜索</span></a>
        </li>
      </ul>
    </div>

      <div class="sBox">
       <div class="subNav sublist-down"><span class="title-icon glyphicon glyphicon-chevron-down"></span><span class="sublist-title">账号管理</span>
        </div>
        <ul class="navContent" style="display:block">
          <li>
            <div class="showtitle" style="width:100px;"><img src="<?php echo YUrl::assets('image', '/frontend/leftimg.png'); ?>" />账号信息</div>
            <a href="<?php echo YUrl::createFrontendUrl('User', 'userinfo'); ?>"><span class="sublist-icon glyphicon glyphicon-user"></span><span class="sub-title">账号信息</span></a>
          </li>
        </ul>
      </div>
    </div>
  </div>