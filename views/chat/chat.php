<?php
$this->title = 'Dots';
$this->registerCssFile('/web/css/chat.css');
$this->registerJsFile('/web/js/chat.js', ['position' => yii\web\View::POS_END]);
?>
<div class="outDiv clearfix">
    <aside>
    </aside>
    <section class="bg-info">
        <div class="div1" id="div1">
            <form name="newMessageForm" class="newMessageForm" id="newMessageForm">
                <p><button type="button" id="sendNewMessage" class="btn btn-primary btn-xs">отправить</button><span class="strHeader">Dots чат</span></p>
                <textarea name="message"></textarea>
            </form>
        </div>
        <div class="div2" id="div2"></div>
    </section>

</form>
</div>
