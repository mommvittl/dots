<?php

/* @var $this yii\web\View */

$this->title = 'Dots';
$this->registerJsFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.css');
$this->registerJsFile('/web/js/game.js', ['position' => yii\web\View::POS_END]);
//var_dump($_SESSION);

?>
<div class="site-index">

    <div class="body-content">

        <div class="row">

            <div id="mapid">Getting your position...</div>
        </div>
        <button onclick="stopWatch()">stop watch</button>

    </div>
</div>