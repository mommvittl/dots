<?php

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
$this->registerJsFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.css');
$this->registerJsFile('/web/js/game.js', ['position' => yii\web\View::POS_END]);

?>
<div class="site-index">

    <div class="body-content">

        <div class="row">
            <div id="mapid"></div>
        </div>

    </div>
</div>