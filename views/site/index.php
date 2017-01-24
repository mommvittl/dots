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
            <button id="ready" disabled onclick="getReady()">ready</button>
            <button id="watch" onclick="stopWatch()">stop watch</button>
            <button id="test" onclick="test()">test</button>
            <input id="testId" type="text" placeholder="enter id..." style="width: 80px">
            <button id="simulation" onclick="simulationSwitch()">simulation</button>
            <button id="gameover" onclick="stopGame()">game over</button>
        </div>
        <div class="row">
            <div class="col-sm-8" id="mapid">Getting your position...</div>
            <div id="prepare" class="col-sm-4">
                <div class="row ">
                    <label for="players">Select opponent</label>
                </div>
                <div class="row">
                    <select id="players" size="10" class="form-control" onchange="selectedOpponent()">
                    </select>
                </div>
                <div class="row">
                    <button id="enemySelect" disabled onclick="enemySelect()">start</button>
                </div>
            </div>
        </div>
        

    </div>
</div>