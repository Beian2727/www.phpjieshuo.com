<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style type="text/css">
html {
	_overflow-y: scroll
}
</style>

<div class="pad_10">
	<form action="<?php echo YUrl::createBackendUrl('News', 'add'); ?>"
		method="post" name="myform" id="myform">
		<table cellpadding="2" cellspacing="1" class="table_form" width="100%">
			<tr>
				<th width="100">文章标题：</th>
				<td><input type="text" name="title" id="title" size="40" class="input-text" value="">(不得超过100个字符)</td>
			</tr>
			<tr>
				<th>分类</th>
				<td>
					<select id="parentCatId">
					<option value="">请选择父分类</option>
					<?php foreach ($news_cat_list as $cat): ?>
						<option value="<?php echo $cat['cat_id']; ?>"><?php echo $cat['cat_name']; ?></option>
					<?php endforeach; ?>
					</select>
					<select id="subCatId" name="cat_code">
						<option value="">请选择子分类</option>
					</select>
				</td>
			</tr>
			<tr>
				<th width="100">关键词：</th>
				<td><input type="text" name="keywords" id="keywords" size="40" class="input-text" value=""></td>
			</tr>
			<tr>
				<th width="100">文章简介：</th>
				<td><textarea name="intro" id="intro" style="width: 600px;" rows="5"
						cols="50"></textarea></td>
			</tr>
			<tr>
				<th width="100">来源：</th>
				<td><input type="text" name="source" id="source" size="20" class="input-text" value=""></td>
			</tr>
			<tr>
				<th width="100">显示状态：</th>
				<td>
				    <select name="display">
						<option value="1">显示</option>
						<option value="0">隐藏</option>
				    </select>
				</td>
			</tr>
			<tr>
				<th width="100">文章主图：</th>
				<td>
				    <input type="hidden" name="image_url" id="input_voucher" value="" />
					<div id="previewImage"></div>
				</td>
			</tr>
			<tr>
				<th width="100">文章内容：</th>
				<td><textarea name="content" id="editor_id" style="width: 700px; height: 400px;" rows="5" cols="50"></textarea></td>
			</tr>
			<tr>
				<td width="100%" align="center" colspan="2">
				    <input id="form_submit" type="button" name="dosubmit" class="btn_submit" value=" 提交 " />
			    </td>
			</tr>
		</table>

	</form>
</div>

<script charset="utf-8" src="<?php echo YUrl::assets('js', '/kindeditor/kindeditor-all.js') ?>"></script>
<script charset="utf-8" src="<?php echo YUrl::assets('js', '/kindeditor/lang/zh-CN.js') ?>"></script>
<script src="<?php echo YUrl::assets('js', '/AjaxUploader/uploadImage.js'); ?>"></script>
<script type="text/javascript">

var uploadUrl = '<?php echo YUrl::createBackendUrl('Index', 'upload'); ?>';
var baseJsUrl = '<?php echo YUrl::assets('js', ''); ?>';
var filUrl    = '<?php echo $files_domain_name; ?>';
uploadImage(filUrl, baseJsUrl, 'previewImage', 'input_voucher', 120, 120, uploadUrl);
var editor;
$(document).ready(function(){
	KindEditor.ready(function(K) {
	    editor = K.create('#editor_id', {
			'items': [ 'source', '|', 'preview', 'template', 'code', '|',
			'justifyleft', 'justifycenter', 'justifyright',
			'clearhtml', 'selectall', 'removeformat', '|', 
			'formatblock', 'bold', 'italic', 'underline', 'strikethrough', '|', 'image',
			'flash', 'media', 'insertfile', 'table', 'baidumap', 'pagebreak',
			'anchor', 'link', 'unlink', 'fullscreen'],
			'cssPath' : '<?php echo YUrl::assets('css', '/backend/kindeditor_custom.css'); ?>',
			'uploadJson' : '<?php echo YUrl::createBackendUrl('News', 'upload'); ?>',
			'allowFileManager' : false,
			'urlType' : 'domain'
		});
	});
	$('#form_submit').click(function(){
		editor.sync();
	    $.ajax({
	    	type: 'post',
            url: $('form').eq(0).attr('action'),
            dataType: 'json',
            data: $('form').eq(0).serialize(),
            success: function(data) {
                if (data.code == 200) {
                	parent.location.reload();
                } else {
                	dialogTips(data.msg, 3);
                }
            }
	    });
	});

	$('#parentCatId').change(function() {
		$.ajax({
	    	type: 'post',
            url: '<?php echo YUrl::createBackendUrl('Category', 'getListJson'); ?>',
            dataType: 'json',
            data: {"cat_type" : 1, "cat_id" : this.value},
            success: function(data) {
                if (data.code == 200) {
					html = '<option value="">请选择子分类</option>';
                	$.each(data.data, function(key, val) {  
						html += '<option value="' + val.cat_code + '">' + val.cat_name + '</option>';
					});
					$('#subCatId').empty();
					$('#subCatId').html(html);
                } else {
                	
                }
            }
	    });
	});
});
</script>

</body>
</html>