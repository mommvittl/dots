<?php

/* @var $this yii\web\View */

$this->title = 'Dots';
$this->registerJsFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/leaflet@1.0.2/dist/leaflet.css');
$this->registerJsFile('/web/js/game.js', ['position' => yii\web\View::POS_END]);
$this->registerJsFile('/web/js/nouislider.js', ['position' => yii\web\View::POS_END]);
$this->registerCssFile('/web/css/nouislider.css');

?>
<div class="site-index">

    <div class="body-content">
        <div id="mode">
            <div class="btn-block">
                <button type="button" class="btn btn-success btn-lg btn-block" onclick="startGPS()">With GPS</button>
                <button type="button" class="btn btn-primary btn-lg btn-block" onclick="startSimulation()">Simulation</button>
                <button type="button" class="btn btn-primary btn-lg btn-block" onclick="getHistory()">Watch replays</button>
            </div>
                <img id="homeImage" src="/images/dots.png" alt="dots">
        </div>
        <div id="game" hidden>
            <div class="row">
                <div>
<!--                    <button id="watch" onclick="stopWatch()">stop watch</button>-->
                    <button id="gameover" onclick="stopGame()" hidden>Surrend</button>
                    <span id="help"></span>
                </div>
            </div>
            <div id="error" class="alert alert-danger" role="alert" hidden></div>
            <div class="row" id="map">
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
            <div id="scores" hidden>
                <div id="myScores"></div>
                <div id="enemyScores"></div>
            </div>
        </div>
        <div class="modal fade" id="finalScores" tabindex="-1" role="dialog" aria-labelledby="Scores">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
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
        <div id="history" hidden>
            <table id="replayList" class="table table-hover">
                <tr>
                    <th>Game id</th>
                    <th>Opponents</th>
                    <th>Winner</th>
                </tr>
            </table>
        </div>
        <div id="replay" hidden>
            <div class="row" id="replayMap"></div>
            <div id="controlButtons">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-default glyphicon glyphicon-backward" onclick="stepBackward()"></button>
                    <button type="button" class="btn btn-default glyphicon glyphicon-play" onclick="stepPlay()"></button>
                    <button type="button" class="btn btn-default glyphicon glyphicon-pause" onclick="stepPause()"></button>
                    <button type="button" class="btn btn-default glyphicon glyphicon-stop" onclick="stepStop()"></button>
                    <button type="button" class="btn btn-default glyphicon glyphicon-forward" onclick="stepForward()"></button>
                </div>
            </div>
            <div id="sliderBar">
                <div id="slider"></div>
                <div class="row">
                    <div class="col-xs-4" id="slider-start"></div>
                    <div class="col-xs-4">
                        <div style="text-align: center" id="slider-val"></div>
                    </div>
                    <div class="col-xs-4" >
                        <div class="pull-right" id="slider-end"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>