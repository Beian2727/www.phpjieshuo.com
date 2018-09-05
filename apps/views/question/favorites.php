<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<div class="right-product view-product right-full">
    <div class="container-fluid">
        <div class="info-center">
            <div class="page-header">
                <div class="pull-left">
                <h4>题目收藏</h4>      
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="table-margin">
            <table class="table table-bordered table-header">
                <thead>
                    <tr>
                        <td class="w55">题目标题</td>
                        <td class="w10">题型</td>
                        <td class="w10">分类</td>
                        <td class="w15">收藏时间</td>
                        <td class="w10">操作</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHP 数据类型有几种？</td>
                        <td>单选</td>
                        <td>PHP 基础</td>
                        <td>2018-01-09 09:39:17</td>
                        <td>删除</td>
                    </tr>
                    <tr>
                        <td>PHP 数据类型有几种？</td>
                        <td>多选</td>
                        <td>PHP 基础</td>
                        <td>2018-01-09 09:39:17</td>
                        <td>删除</td>
                    </tr>
                </tbody>
            </table>
            </div>
            </div>
            <div class="show-page hidden">
                <ul></ul>
            </div>
		</div>
  </div>
</div>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>