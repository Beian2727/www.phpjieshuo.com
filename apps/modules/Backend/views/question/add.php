<?php
use common\YUrl;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style type="text/css">
html {
	_overflow-y: scroll
}
</style>

<div class="pad_10">
	<form action="<?php echo YUrl::createBackendUrl('Question', 'add'); ?>" method="post" name="myform" id="myform">
		<table cellpadding="2" cellspacing="1" class="table_form" width="100%">
			<tr>
				<th width="100">题目标题：</th>
				<td>
					<textarea rows="5" cols="50" name="title"></textarea>
				</td>
			</tr>
			<tr>
				<th width="100">题目图片：</th>
				<td>
					<input type="hidden" name="image_url" id="image_url" value="" />
					<div id="image_url_view"></div>
				</td>
			</tr>
			<tr>
				<th>分类</th>
				<td>
					<select id="parentCatId">
					<option value="">请选择父分类</option>
					<?php foreach ($cat_list as $cat): ?>
						<option value="<?php echo $cat['cat_id']; ?>"><?php echo $cat['cat_name']; ?></option>
					<?php endforeach; ?>
					</select>
					<select id="subCatId" name="cat_code">
						<option value="">请选择子分类</option>
					</select>
				</td>
			</tr>
			<tr>
				<th width="100">正确结果：</th>
				<td>
					<?php foreach($options as $opk => $opv): ?>
					<label><input type="checkbox" name="answer[]" value="<?php echo $opk; ?>" /><?php echo $opv; ?></label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th width="100">选项：</th>
				<td>
					<?php foreach($options as $opk => $opv): ?>
					<p style="padding-top: 10px;">
						<input type="text" size="30" class="input-text" name="options_data[<?php echo $opk; ?>][op_title]"></input>[<?php echo $opv; ?>]
					</p>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th width="100">答案解析：</th>
				<td>
					<textarea rows="8" cols="50" name="explain"></textarea>
				</td>
			</tr>
			<tr>
				<td width="100%" align="center" colspan="2">
					<input id="form_submit" type="button" name="dosubmit" class="btn_submit" value=" 提交 " />
				</td>
			</tr>
		</table>

	</form>
</div>

<script src="<?php echo YUrl::assets('js', '/AjaxUploader/uploadImage.js'); ?>"></script>
<script type="text/javascript">

var uploadUrl = '<?php echo YUrl::createBackendUrl('Index', 'upload'); ?>';
var baseJsUrl = '<?php echo YUrl::assets('js', ''); ?>';
var filUrl    = '<?php echo YUrl::getDomainName(); ?>';
uploadImage(filUrl, baseJsUrl, 'image_url_view', 'image_url', 240, 160, uploadUrl);

$(document).ready(function(){
	$('#form_submit').click(function(){
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
            data: {"cat_type" : 2, "cat_id" : this.value},
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