<?php
use common\YUrl;
use common\YCore;
require_once (dirname(__DIR__) . '/question/header.php');
?>

<style>
    .question-panel {
        padding: 10px 10px 0px 10px;
    }
    .question-header {
        background-color: #f2f2f2;
    }
    .question-title {
        padding: 10px;
        color: #666;
        line-height: 24px;
        font-weight: bold;
        font-size: 1.2em;
    }
    .question-title .question-type {
        color: #F60;
    }
    .question-image {
        margin: 5px;
        padding-bottom: 10px;
        text-align: left;
    }
    .question-image img {
        height: 200px;
    }
    .question-explain {
        margin-top: 10px;
        background-color: #f2f2f2;
        padding: 5px;
        line-height: 22px;
        display: none;
    }
    .question-explain .layui-field-title {
        margin:10px 0 10px;
    }
    .question-explain .layui-field-title legend {
        font-size: 16px;
    }
    .question-option {
        
    }
    .question-option-item {
        margin: 10px 0px;
        line-height: 22px;
        background-color: #F2F2F2;
        padding: 10px;
    }
    .question-option-item label {
        cursor: pointer;
    }
    .question-option-submit {
        margin: 10px;
    }
    .question-footer {
        margin: 10px;
    }
</style>

<div class="question-panel">
    <div class="question-header">
        <div class="question-title">-</div>
        <div class="question-image"></div>
    </div>

    <div class="question-option"></div>

    <div class="question-option-submit">
        <button class="layui-btn layui-btn-danger layui-btn-radius question-option-submit-ok">确定 我选好了</button>
    </div>

    <div class="question-explain">
        <fieldset class="layui-elem-field layui-field-title">
            <legend>题目解析</legend>
        </fieldset>
        <div class="question-explain-content"></div>
    </div>

    <div class="question-footer">
        <button class="layui-btn btn-prev">上一题</button>
        <button class="layui-btn btn-next">下一题</button>
    </div>
</div> 

<script>
$(function() {

    var questionCatId = <?=$catId?>;    // 分类ID。
    var questionJson;                   // 题目 JSON。
    var questionIndex = 0;              // 当前答题位置。
    var questionTotal;                  // 题目总数。

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

        /**
         * 下一题。
         */
		$('.btn-next').click(function() {
            questionIndex += 1;
            if (questionIndex > (questionTotal-1)) {
                questionIndex -= 1;
                layer.msg('已经是最后一题了');
            }
            question = questionJson[questionIndex];
            rendorQuestion(question.title, question.image_url, question.explain, question.answer, question.option_data.A.op_title,
            question.option_data.B.op_title, question.option_data.C.op_title, question.option_data.D.op_title, question.option_data.E.op_title);
        });

        /**
         * 上一题。
         */
        $('.btn-prev').click(function() {
            questionIndex -= 1;
            if (questionIndex < 0) {
                questionIndex += 1;
                layer.msg('已经是第一题了');
            }
            question = questionJson[questionIndex];
            rendorQuestion(question.title, question.image_url, question.explain, question.answer, question.option_data.A.op_title,
            question.option_data.B.op_title, question.option_data.C.op_title, question.option_data.D.op_title, question.option_data.E.op_title);
        });

        /**
         * 提交答案。
         */
        $('.question-option-submit-ok').click(function() {
            var answer = '';
            var selectedVals = $('input[name="answer[]"]:checked').each(function(){ 
                answer += ',' + $(this).val(); 
            });
            answer = answer.substr(1, answer.length);
            if (answer.length == 0) {
                layer.msg('请选择答案');
                return;
            }
            if (answer != questionJson[questionIndex].answer) {
                layer.msg('回答错误');
                $('.question-explain').show();
            } else {
                // 下一题。
                questionIndex += 1;
                if (questionIndex <= (questionTotal-1)) {
                    question = questionJson[questionIndex];
                    rendorQuestion(question.title, question.image_url, question.explain, question.answer, question.option_data.A.op_title,
                    question.option_data.B.op_title, question.option_data.C.op_title, question.option_data.D.op_title, question.option_data.E.op_title);
                } else {
                    layer.msg('最后一题回答正确!');
                }
            }
        });
	});

    /**
     * 渲染题目。
     * @param string title      题目。
     * @param string imageUrl   题目图片。
     * @param string explain    题目分析。
     * @param string answer     题目答题。
     * @param string a          题目 A 选项。
     * @param string b          题目 B 选项。
     * @param string c          题目 C 选项。
     * @param string d          题目 D 选项。
     * @param string e          题目 E 选项。
     */
    function rendorQuestion(title, imageUrl, explain, answer, a, b, c, d, e)
    {
        
        title += answer.length > 1 ? '<span class="question-type">[多选题]</span>' : '<span class="question-type">[单选题]</span>';
        $('.question-title').html(title);
        $('.question-image').empty();
        if (imageUrl.length > 0) {
            $('.question-image').html("<a target=\"_blank\" href=\""+imageUrl+"\"><img src="+imageUrl+"></a>");
        }
        $('.question-option').empty();
        var quesType = answer.length > 1 ? 'checkbox' : 'radio';
        if (a.length > 0) {
            var optItem = '<div class="question-option-item"><label><input type="'+quesType+'" name="answer[]" value="A" /> A、' + a + '</label></div>';
            $('.question-option').append(optItem);
        }
        if (b.length > 0) {
            var optItem = '<div class="question-option-item"><label><input type="'+quesType+'" name="answer[]" value="B" /> B、' + b + '</label></div>';
            $('.question-option').append(optItem);
        }
        if (c.length > 0) {
            var optItem = '<div class="question-option-item"><label><input type="'+quesType+'" name="answer[]" value="C" /> C、' + c + '</label></div>';
            $('.question-option').append(optItem);
        }
        if (d.length > 0) {
            var optItem = '<div class="question-option-item"><label><input type="'+quesType+'" name="answer[]" value="D" /> D、' + d + '</label></div>';
            $('.question-option').append(optItem);
        }
        if (e.length > 0) {
            var optItem = '<div class="question-option-item"><label><input type="'+quesType+'" name="answer[]" value="E" /> E、' + e + '</label></div>';
            $('.question-option').append(optItem);
        }
        // 题目解析
        var explain = "正确答案:" + answer + "<br/>" + explain;
        $('.question-explain-content').html(explain);
        $('.question-explain').hide();
    }

    // 请求题目数据。
    $.ajax({
        type: 'get',
        url: '<?php echo YUrl::createFrontendUrl('Question', 'get', ['cat_id' => $catId]); ?>',
        dataType: 'json',
        data: {},
        success: function(data) {
            questionIndex = sessionStorage.getItem(questionCatId);
            questionIndex = questionIndex ? questionIndex : 0;
            questionTotal = data.length;
            questionJson  = data;
            question      = questionJson[questionIndex];
            rendorQuestion(question.title, question.image_url, question.explain, question.answer, question.option_data.A.op_title,
            question.option_data.B.op_title, question.option_data.C.op_title, question.option_data.D.op_title, question.option_data.E.op_title);
        }
    });

});

</script>

<?php
require_once (dirname(__DIR__) . '/question/footer.php');
?>