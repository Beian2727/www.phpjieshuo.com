//滚动条
$(function() {
	$(":text").addClass('input-text');
})

/**
 * 全选checkbox,注意：标识checkbox id固定为为check_box
 * @param string name 列表check名称,如 uid[]
 */
function selectall(name) {
	if ($('#check_box').is(':checked')) {
		$("input[name='"+name+"']").each(function() {
  			$(this).prop("checked", true);
			
		});
	} else {
		$("input[name='"+name+"']").each(function() {
  			$(this).prop("checked", false );
		});
	}
}

/**
 * airDialog5版弹出框tips。
 * @param message 提示内容。
 * @param interval 间隔时间。单位秒。
 * @return void
 */
function dialogTips(message, interval) {
	layer.msg(message, {
		id : 'dialogTips' + '_' + Math.random(),
		icon: 0,
		time: interval * 1000
	}, function(){
		//do something
	});
}

/**
 * 弹出一个添加/编辑操作的对话框。
 * @param dialog_id 弹出框的ID。
 * @param page_url 表单页面URL。
 * @param title 弹出框名称。
 * @param scrolling ifream是否滚动。yes、no。
 * @return void
 */
function postDialog(dialog_id, page_url, dialog_title, dialog_width, dialog_height, scrolling) {
	layer.open({
		id: dialog_id,
		type: 2,
		title: dialog_title,
		area: [dialog_width+'px', dialog_height+'px'],
		shade: false,
		shadeClose: true,
		scrollbar:scrolling,
		content: page_url
	});
}

/**
 * 弹出一个普通操作的对话框（类似于Yes or No这样简单的对话框）。
 * @param dialog_id 弹出框的ID。
 * @param request_url 操作请求的URL。
 * @param title 操作提示。
 * @return void
 */
function normalDialog(dialog_id, request_url, title) {
	layer.confirm('您确定要删除【' + title + '】吗？', {
		btn: ['确定', '取消']
	}, 
	function() {
		$.ajax({
			type: "GET",
			url: request_url,
			dataType: 'json',
			success: function(data){
				if (data.code == 500) {
					d.close();
					dialogTips(data.msg, 5);
				} else {
					location.reload();
				}
			}
		});
	},
	function(){
		
	});
}

/**
 * 弹出一个删除操作的对话框。
 * @param dialog_id 弹出框的ID。
 * @param request_url 执行删除操作的URL。
 * @param title 要删除的记录的标题或名称。
 * @return void
 */
function deleteDialog(dialog_id, request_url, title) {
	layer.confirm('您确定要删除【' + title + '】吗？', {
		btn: ['确定', '取消']
	}, 
	function() {
		$.ajax({
			type: "GET",
			url: request_url,
			dataType: 'json',
			success: function(data){
				if (data.code == 200) {
					location.reload();
				} else {
					dialogTips(data.msg, 3);
					return false;
				}
			}
		});
	},
	function(){
		// 点击取消按钮啥事也不做。
	});
}