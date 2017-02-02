<?php

/* @var $this yii\web\View */

$this->title = 'Dots';
$this->registerJsFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.css');
$this->registerJsFile('/web/js/game.js', ['position' => yii\web\View::POS_END]);
//var_dump($_SESSION);
var_dump($this->jsFiles);

?>
<div class="site-index">

    <div class="body-content">
        <div id="mode">
            <button onclick="startGPS()">With GPS</button>
            <button onclick="startSimulation()">simulation</button>
        </div>
        <div id="game" hidden>
            <div class="row">
                <div>
                    <button id="watch" onclick="stopWatch()">stop watch</button>
                    <button id="gameover" onclick="stopGame()" hidden>game over</button>
                    <span id="help"></span>
                    <span id="myScore"></span>
                    <span id="enemyScore"></span>
                </div>
            </div>
            <div id="error" class="alert alert-danger" role="alert" hidden></div>
            <div class="row">
                <div class="col-sm-8" id="mapid"></div>
                <div id="prepare" class="col-sm-4">
                    <div class="row ">
                        <label for="players">Select opponent</label>
                    </div>
                    <div class="row">
                        <select id="players" size="10" class="form-control" onchange="selectedOpponent()">
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="finalScores" tabindex="-1" role="dialog" aria-labelledby="Scores">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
<!--                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                        <h4 class="modal-title" id="Scores">Final Scores</h4>
                    </div>
                    <div class="modal-body">
                        <h2 id="winner"></h2>
                        <p id="scoresMe"></p>
                        <p id="scoresEnemy"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="restart()">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>