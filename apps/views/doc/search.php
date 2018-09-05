<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/common/header.php');
?>

<style type="text/css">


</style>

<div class="right-product view-product right-full">
    <div class="container-fluid">

        <?php foreach ($result['list'] as $news): ?>
            <div class="pictxt clearfix">
                <div class="txt">
                    <h5><a href="<?php echo YUrl::createFrontendUrl('Doc', 'view', ['cat_id' => $news['cat_id'], 'news_id' => $news['news_id']])?>" target="_blank"><?php echo $news['title']; ?></a></h5>
                    <p><?php echo $news['intro']; ?>&nbsp;<a href="<?php echo YUrl::createFrontendUrl('Doc', 'view', ['cat_id' => $news['cat_id'], 'news_id' => $news['news_id']])?>" target="_blank">详细</a></p>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<script>

</script>

<?php
require_once (dirname(__DIR__) . '/common/footer.php');
?>