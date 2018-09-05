<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style>
.loginDiv {
    clear:both;
    margin: 200px auto;
    width: 500px;
}
.layui-input {
    width: 300px;
}
</style>

<div class="loginDiv">
    <form class="layui-form" method="POST" action="">
    <div class="layui-form-item">
        <label class="layui-form-label">旧密码</label>
        <div class="layui-input-block">
        <input type="password" name="oldPwd" required lay-verify="required" placeholder="请输入旧密码" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">新密码</label>
        <div class="layui-input-inline">
        <input type="password" name="newPwd" required lay-verify="required" placeholder="请输入新密码" autocomplete="off" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-input-block">
        <button class="layui-btn" lay-submit lay-filter="formDemo">立即提交</button>
        </div>
    </div>
    </form>
    
    <script>
    layui.use('form', function(){
        var form = layui.form;
    });
    </script>
</div>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>