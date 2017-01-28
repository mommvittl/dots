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
        <div id="mode">
            <button onclick="startGPS()">With GPS</button>
            <button onclick="startSimulation()">simulation</button>
        </div>
        <div id="game" hidden>
            <div class="row">
                <button id="ready" disabled>ready</button>
                <button id="watch" onclick="stopWatch()">stop watch</button>
                <button id="gameover" onclick="stopGame()" hidden>game over</button>
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
                    <!--<div class="row">
                        <button id="enemySelect" disabled onclick="enemySelect()">start</button>
                    </div>-->
                </div>
            </div>
        </div>
    </div>
</div>