<?php
use common\YCore;
use common\YUrl;
require_once (dirname(__DIR__) . '/common/header.php');
?>
<div class="subnav"></div>
<style type="text/css">
html {
	_overflow-y: scroll
}
</style>
<div class="pad-lr-10">

	<form name="myform" id="myform" action="?m=goods&c=shop&a=listorder"
		method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="center">账号</th>
						<th align="center">登录时间</th>
						<th align="center">IP地址</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($list as $item): ?>
					<tr>
						<td align="center"><?php echo $item['username']; ?></td>
						<td align="center"><?php echo $item['created_time']; ?></td>
						<td align="center"><?php echo $item['ip']; ?></td>
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

</script>
</body>
</html>