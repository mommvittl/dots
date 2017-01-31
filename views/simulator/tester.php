<?php
$this->title = 'Dots';
$this->registerJsFile('https://api-maps.yandex.ru/2.1/?lang=ru_RU', ['position' => yii\web\View::POS_HEAD]);
$this->registerJsFile('/web/js/tester.js', ['position' => yii\web\View::POS_END]);
?>

<div id="informStr" style="position: fixed; left: 10px; bottom: 5px; z-index: 1000; padding: 5px; width: 70vw; background: black; color: white; font: 30px/33px arial; text-align: center;">Кликните старт</div>
<BUTTON type="button" id="gameControl" style=" position: fixed; right: 10px; bottom: 10px; z-index: 1000; padding: 5px;width: 20vw; height: 10vh; cursor: pointer; background: #FF7F50; font-size: 18px; ">START</BUTTON>

<div id="YMapsID" style="  height: 100vh; position: relative; margin: 0 auto;  "></div>
    


