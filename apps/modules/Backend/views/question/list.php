<?php
use common\YUrl;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<div class="subnav">
	<div class="content-menu ib-a blue line-x">
		<a class="add fb"
			href="javascript:postDialog('addQuestion', '<?php echo YUrl::createBackendUrl('Question', 'add'); ?>', '添加题库', 600, 750)"><em>添加题库</em></a>
		<a href='javascript:;' class="on"><em>题库列表</em></a>
	</div>
</div>
<style type="text/css">
html {
	_overflow-y: scroll
}
</style>
<div class="pad-lr-10">

	<form name="searchform" action="" method="get">
		<table width="100%" cellspacing="0" class="search-form">
			<tbody>
				<tr>
					<td>
						<div class="explain-col">
							    题目标题：<input type="text" name="title" value="" class="input text" />
							    时间：<input type="text" name="start_time" id="start_time" value="<?php echo $start_time; ?>" size="20" class="date input-text" />
								～ <input type="text" name="end_time" id="end_time" value="<?php echo $end_time; ?>" size="20" class="date input-text" />
							    <input type="submit" name="search" class="button" value="搜索" />
							</p>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</form>

	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="center">题目ID</th>
						<th align="left">题目标题</th>
						<th align="center">题目图片</th>
						<th align="center">正确结果</th>
						<th align="center">总参与次数</th>
						<th align="center">总参与人数</th>
						<th align="center">总正确次数</th>
						<th align="center">总错误次数</th>
						<th width="120" align="center">修改时间</th>
						<th width="120" align="center">创建时间</th>
						<th width="120" align="center">管理操作</th>
					</tr>
				</thead>
				<tbody>
                <?php foreach ($list as $item): ?>
    	           <tr>
						<td align="center"><?php echo $item['question_id']; ?></td>
						<td align="left"><?php echo $item['title']; ?></td>
						<td align="center"><img alt="题目图片" src="<?php echo $item['image_url']; ?>" width="50" /></td>
						<td align="center"><?php echo $item['answer']; ?></td>
						<td align="center"><?php echo $item['total_times']; ?></td>
						<td align="center"><?php echo $item['total_people']; ?></td>
						<td align="center"><?php echo $item['total_ok_time']; ?></td>
						<td align="center"><?php echo $item['total_error_times']; ?></td>
						<td align="center"><?php echo $item['modified_time']; ?></td>
						<td align="center"><?php echo $item['created_time']; ?></td>
						<td align="center">
						<a href="javascript:postDialog('GuessUsers', '<?php echo YUrl::createBackendUrl('Question', 'record', ['question_id' => $item['question_id']]); ?>', '参与竞猜的用户列表', 800, 600)">参与列表</a><br />
						  <a href="###" onclick="edit(<?php echo $item['question_id'] ?>, '<?php echo htmlspecialchars($item['title']) ?>')" title="修改">修改</a> |
						  <a href="###" onclick="deleteDialog('deleteGuess', '<?php echo YUrl::createBackendUrl('Question', 'delete', ['question_id' => $item['question_id']]); ?>', '<?php echo htmlspecialchars($item['title']) ?>')" title="删除">删除</a>
						</td>
			       </tr>
                <?php endforeach; ?>
                </tbody>
			</table>

			<div id="pages">
			<?php echo $page_html; ?>
			</div>

		</div>

	</form>
</div>
<script type="text/javascript">
Calendar.setup({
	weekNumbers: false,
    inputField : "start_time",
    trigger    : "start_time",
    dateFormat: "%Y-%m-%d %H:%I:%S",
    showTime: true,
    minuteStep: 1,
    onSelect   : function() {this.hide();}
});

Calendar.setup({
	weekNumbers: false,
    inputField : "end_time",
    trigger    : "end_time",
    dateFormat: "%Y-%m-%d %H:%I:%S",
    showTime: true,
    minuteStep: 1,
    onSelect   : function() {this.hide();}
});

function edit(id, name) {
	var title = '修改『' + name + '』';
	var page_url = "<?php echo YUrl::createBackendUrl('Question', 'edit'); ?>?question_id="+id;
	postDialog('editGuess', page_url, title, 600, 750);
}
</script>
</body>
</html>