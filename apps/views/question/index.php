<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style>
    .custom_btn {
        width: 140px;
        height: 100px;
        font-size: 26px;
    }
	.category-list {
		width: 100%;
	}
	.category-list-sub {
		margin-left: 0px;
	}
	.btn-cat {
		margin-left: 0px;
		margin-top: 10px;
		width: 140px;
	}
	.layui-btn+.layui-btn {
		margin-left: 0px;
	}
</style>

<div class="right-product view-product right-full">
    <div class="container-fluid" style="margin-top:40px;">
        <button class="layui-btn custom_btn" data="0">随机练习</button>
    </div>

	<div class="category-list">
		<?php foreach($catList as $cat): ?>
		<button class="layui-btn layui-btn-fluid btn-cat" data="<?=$cat['cat_id']?>"><?=$cat['cat_name']?></button>
		<div class="category-list-sub">
			<?php foreach($cat['sub'] as $subCat): ?>
			<button class="layui-btn layui-btn-fluid btn-cat" data="<?=$subCat['cat_id']?>"><?=$subCat['cat_name']?></button>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>

</div>

<script>

$(function() {

	layui.use('layer', function() {
		var $ = layui.jquery, layer = layui.layer;
		//触发事件
		var active = {
			questionDialg: function(dialog_id, page_url, dialog_title) {
				var that = this;
				layer.open({
					id: dialog_id,
					type: 2,
					title: dialog_title,
					area: ['600px', '800px'],
					shade: true,
					shadeClose: false,
					scrollbar:true,
					content: page_url
				});
			}
		};

		$('.btn-cat').click(function() {
			var url     = '<?php echo YUrl::createFrontendUrl('Question', 'startDo'); ?>';
			var catId   = $(this).attr('data');
			var catName = $(this).text();
			active.questionDialg(catId, url+'?cat_id='+catId, catName);
		});
	});

});

</script>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>