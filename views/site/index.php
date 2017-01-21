<?php

/* @var $this yii\web\View */

$this->title = 'Dots';
$this->registerJsFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.css');
$this->registerJsFile('/web/js/game.js', ['position' => yii\web\View::POS_END]);

?>
<div class="site-index">

    <div class="body-content">

        <div class="row">
            <div class="col-md-8" id="mapid">Getting your position...</div>
            <div class="col-md-4">
                <div class="row ">
                    <label for="players">Select opponent</label>
                </div>
                <select id="players" size="10" class="form-control">
<!--                    <option value="">-- select country --</option>-->
                </select>
            </div>
        </div>
        <button onclick="stopWatch()">stop watch</button>

    </div>
</div>