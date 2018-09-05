<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style type="text/css">
blockquote {
    font-size:14px;
}
.site-title fieldset legend {
    margin-left: 20px;
    padding: 0 10px;
    font-size: 22px;
    font-weight: 300;
    margin-bottom: 0px;
    width: auto;
    border-bottom: none;
}

.site-content .site-content-doc table {
    width: 100%;
    margin: 10px 0;
    border-collapse: collapse;
    border-spacing: 0;
}

.site-content .site-content-doc img {
    max-width: 95%;
}

.site-content .site-content-doc table tbody {
    display: table-row-group;
    vertical-align: middle;
    border-color: inherit;
}

.site-content .site-content-doc table tbody tr {
    display: table-row;
    vertical-align: inherit;
    border-color: inherit;
}

.site-content .site-content-doc table td,.site-content .site-content-doc table th {
    padding: 6px 15px;
    min-height: 20px;
    line-height: 20px;
    border: 1px solid #ddd;
    font-size: 14px;
    font-weight: 400;
}

.site-content .site-content-doc blockquote {
    position: relative;
    margin-bottom: 10px;
    padding: 15px;
    line-height: 22px;
    border-left: 5px solid #009688;
    border-radius: 0 2px 2px 0;
    background-color: #f2f2f2;
    font-size: 14px;
}

.site-content .site-content-doc h1,h2,h3,h4,h5 {
    padding: 0px;
    font-weight: 300;
    margin: 15px 0px;
    width: auto;
    border-bottom: none;
    font-weight: bold;
}

.site-content .site-content-doc h1 {
    font-size: 22px;
}

.site-content .site-content-doc h2 {
    font-size: 20px;
}

.site-content .site-content-doc h3 {
    font-size: 18px;
}

.site-content .site-content-doc h4 {
    font-size: 16px;
}

.site-content .site-content-doc h5 {
    font-size: 14px;
}

.site-content .site-content-doc p {
    position: relative;
    margin-bottom: 10px;
    line-height: 22px;
}

.site-content .site-content-doc ul, .site-content .site-content-doc ol {
    margin: 10px 0px;
}

.site-content .site-content-doc li {
    list-style-type: circle;
    margin-left: 20px;
    line-height: 20px;
    font-size: 14px;
}

.site-content .site-content-doc a {
    color: #01AAED;
}

.site-content .site-content-doc pre {
    padding: 10px;
    margin: 10px 0px;
}

.site-content pre {
    display: block;
    padding: 9.5px;
    margin: 0 0 10px;
    font-size: 13px;
    line-height: 1.42857143;
    color: #333;
    word-break: break-all;
    word-wrap: break-word;
    background-color: #f5f5f5;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.layui-tree {
    padding-bottom: 30px;
}

figure {
    display: block;
    -webkit-margin-before: 1em;
    -webkit-margin-after: 1em;
    -webkit-margin-start: 0px;
    -webkit-margin-end: 0px;
}

</style>

<div class="right-product view-product right-full">
    <div class="container-fluid">
        <div>

            <div class="site-inline">
                <div class="site-tree" id="layui-main__left">

                    <!-- 文档分类:start -->
                    <div class="layui-form" style="height: 38px; margin: 10px 13px 0 0;">
                        <select lay-filter="doc">
                            <?php foreach($catList as $cat): ?>
                            <option <?php echo ($cat['cat_id'] == $catId) ? 'selected="selected"' : ''; ?> value="<?php echo $cat['cat_id']; ?>"><?php echo $cat['cat_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- 文档分类:end -->

                    <!-- 文档目录:start -->
                    <div >
                        <ul class="layui-tree">
                            <?php foreach($catalogue as $item): ?>
                            <li><h2><?php echo $item['catInfo']['cat_name']; ?></h2></li>
                            <?php foreach ($item['news'] as $news): ?>
                            <li class="site-tree-noicon ">
                                <a href="<?php echo YUrl::createFrontendUrl('Doc', 'view', ['cat_id' => $catId, 'news_id' => $news['news_id']]); ?>">
                                    <cite><?php echo htmlspecialchars($news['title']); ?></cite>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- 文档目录:end -->
                </div>

                <!-- 文档详情正文:start -->
                <div class="site-content">
                    <h1 class="site-h1"><?php echo htmlspecialchars($detail['title']); ?></h1>
                    <div class="site-content-doc">
                        <?php echo $detail['content']; ?>
                    </div>
                </div>
                <!-- 文档详情正文:end -->
            </div>
		</div>
    </div>
</div>

<script>

layui.use('form', function(){
  var form = layui.form;
  var url = '<?php echo YUrl::createFrontendUrl('Doc', 'view'); ?>';
    form.on('select(doc)', function(data){
        window.location.href = url + "?cat_id=" + data.value;
    });
});

document.getElementById('layui-main__left').style.height= window.innerHeight-50 + "px"
</script>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>