<?php
require_once (dirname(__DIR__) . '/common/header.php');
?>

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
							<p style="margin-top: 10px;">
							手机号码：<input name="mobilephone" type="text" class="input-text" placeholder="手机号码" value="<?php echo $mobilephone; ?>" />
							用户账号：<input name="username" type="text" class="input-text" placeholder="用户账号" value="<?php echo $username; ?>" />
							    <input type="hidden" name="question_id" value="<?php echo $question_id; ?>" />
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
						<th align="left">用户账号</th>
						<th align="center">手机号码</th>
						<th align="center">用户答案</th>
						<th align="center">正确与否</th>
						<th align="center">参与时间</th>
					</tr>
				</thead>
				<tbody>
                <?php foreach ($list as $item): ?>
    	           <tr>
						<td align="left"><?php echo $item['username']; ?></td>
						<td align="center"><?php echo $item['mobilephone']; ?></td>
						<td align="center"><?php echo $item['user_answer']; ?></td>
						<td align="center"><?php echo $item['right_or_no']; ?></td>
						<td align="center"><?php echo $item['created_time']; ?></td>
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

</body>
</html>