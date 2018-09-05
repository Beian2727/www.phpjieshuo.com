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
	<table cellpadding="2" cellspacing="1" class="table_form" width="100%">

		<tr>
			<th width="100">日志ID：</th>
			<td><?php echo $detail['log_id']; ?></td>
		</tr>
		
		<tr>
			<th width="100">日志产生时间：</th>
			<td><?php echo $detail['log_time']; ?></td>
		</tr>
		<tr>
			<th width="100">错误码：</th>
			<td><?php echo $detail['errcode']; ?></td>
		</tr>
		<tr>
			<th width="100">日志内容：</th>
			<td><textarea rows="5" cols="40" style="width: 650px; height: 350px;"
					readonly="readonly"><?php echo htmlspecialchars($detail['content']); ?></textarea></td>
		</tr>
		<tr>
			<th width="100">日志入库时间：</th>
			<td><?php echo $detail['created_time']; ?></td>
		</tr>
	</table>

</div>

<script type="text/javascript">

</script>
</body>
</html>