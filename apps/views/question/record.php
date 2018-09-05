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
                <h4>答题记录</h4>      
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="table-margin">
            <table class="table table-bordered table-header">
                <thead>
                    <tr>
                        <td class="w10">分类</td>
                        <td class="w75">标题</td>
                        <td class="w15">答题时间</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHP 基础</td>
                        <td>PHP SESSION 分布式共享</td>
                        <td>2018-01-09 09:39:17</td>
                    </tr>
                    <tr>
                        <td>PHP 基础</td>
                        <td>PHP SESSION 分布式共享</td>
                        <td>2018-01-09 09:39:17</td>
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