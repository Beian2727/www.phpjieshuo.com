<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style type="text/css">
.grid-doc {
    margin-top: 20px;
}
.grid-doc-cell {
    background-color: #009688;
    line-height: 50px;
    text-align: center;
    padding: 10px;
    color: #FFF;
    font-size: 20px;
    font-weight: bold;
}
.layui-col-md3 a {
    text-decoration: none;
}

.layui-form {
    margin: 100px auto;
    width:600px;
}
.search #in{
    width:454px;
    height:40px;
    border:1px solid #e6e6e6;
    outline:none;
    font:14px/40px arial;
    padding-left: 5px;
}
.search .btn_search{
    position: relative;
    left: -2px;
    top: 0px;
    cursor: pointer;
    background:#009688;
    width:80px;
    height:42px;
    color:#FFFFFF;
    border:none;
    outline:none;
    font:14px/38px arial;
}
.right-full {
    left:0px;
}
</style>

<div class="right-product view-product right-full">
    <div class="container-fluid">
        <div class="table-margin layui-container">

            <form class="layui-form" action="<?php echo YUrl::createFrontendUrl('Doc', 'search'); ?>">
                <div class="search">
                    <input type="text" id="in" value="" autocomplete="off" name="keywords"/><button class="btn_search">搜索</button>
                </div>
            </form>

            <div class="grid-doc layui-row layui-col-space5">
                <?php foreach ($catList as $catInfo): ?>
                <div class="layui-col-md3">
                    <a href="<?php echo YUrl::createFrontendUrl('Doc', 'view', ['cat_id' => $catInfo['cat_id']]); ?>"><div class="grid-doc-cell"><?php echo $catInfo['cat_name']; ?></div></a>
                </div>
                <?php endforeach; ?>

            </div>
		</div>
    </div>
</div>

<script>

</script>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>