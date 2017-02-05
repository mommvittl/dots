<?php

namespace app\controllers;

use app\models\User_has_points;
use app\models\User_has_polygons;
use app\models\Deleted_points;
use app\models\Deleted_polygons;
use app\controllers\BasisController;
use app\models\Game;

class HistoryController extends BasisController {

    protected $arrAddDots = [];
    protected $arrAddPolygon = [];
    protected $arrIdDeleteDots = [];
    protected $arrIdDeletePolygon = [];
    protected $startTime;
    protected $stopTime;

    public function actionIndex() {
        $row = $this->actionGetHistorylist();
        var_dump(  $row  );
    }

     public function actionGetHistorylist() {
         $query = Game::find()
                ->select( 'g.id as id, g.user1_id as idg1,  g.user2_id as idg2, u1.username as gm1, u2.username as gm2,`start_time`,'
                        . '`stop_time`, u3.username as wn,`user1_scores`,`user2_scores`' )
                ->from( '`game` as g , `user` as u1, `user` as u3, `user`as u2' )
                ->where( 'g.`user1_id` = u1.id AND g.`user2_id` = u2.id AND g.`winner_id` = u3.id' )
               ->asArray()
                ->all();
          $this->sendRequest(['status' => 'ok', 'historyList' => $query ]);
    }

    public function actionHistory() {
        $startTime = microtime(true);

        $strParameter = file_get_contents('php://input');
        $newTiming = json_decode($strParameter, TRUE);

        if (!$this->timingValidate($newTiming)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data']);
        }


        $this->arrAddDots = $this->getDotsForAdd();

        $this->arrAddPolygon = $this->getPolygonForAdd();
        $this->arrIdDeleteDots = $this->getDotsForDelete();
        $this->arrIdDeletePolygon = $this->getPolygonForDelete();

        $request = [
            'status' => 'ok',
            'arrAddDots' => $this->arrAddDots,
            'arrAddPolygon' => $this->arrAddPolygon,
            'arrIdDeleteDots' => $this->arrIdDeleteDots,
            'arrIdDeletePolygon' => $this->arrIdDeletePolygon,
            'lastDelDotId' => 0,
            'lastDelPolygonId' => 0,
            'myScores' => 0,
            'enemyScores' => 0,
            'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'time2' => microtime(true) - $startTime
        ];
        $this->sendRequest($request);
        // $this->sendRequest(['status' => 'ok']);
    }

    //=============================================================================
    protected function timingValidate($param) {

        if (!is_array($param) || !isset($param['idGame']) || !isset($param['idGamer']) || !isset($param['idEnemy']) || !isset($param['startTime']) || !isset($param['stopTime'])) {
            return FALSE;
        }

        if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $param['startTime'])) {
            return FALSE;
        }
        if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $param['stopTime'])) {
            return FALSE;
        }
        $this->idGame = (int) $param['idGame'];
        $this->idGamer = (int) $param['idGamer'];
        $this->idEnemy = (int) $param['idEnemy'];
        $this->startTime = $param['startTime'];
        $this->stopTime = $param['stopTime'];

        return $this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy);
    }

    protected function getDotsForAdd() {

        $dots = [];
        $query = User_has_points::find()
                ->select(" `id`, `user_id`, X( `point` ), Y( `point` ), `accuracy`, `timestamp` ")
                ->where('  `game_id` = :idGame  and `timestamp` > :startTime and `timestamp` <= :stopTime ')
                ->addParams([
                    ':idGame' => $this->idGame,
                    ':startTime' => $this->startTime,
                    ':stopTime' => $this->stopTime])
                ->orderBy(' `id` ASC ')
                ->asArray()
                ->all();

        foreach ($query as $value) {
            $gamer = ( $value['user_id'] == $this->idGamer ) ? 'me' : 'opponent';
            $dots[] = [
                'gamer' => $gamer,
                'id' => $value['id'],
                'latitude' => $value['X( `point` )'],
                'longitude' => $value['Y( `point` )'],
                'accuracy' => $value['accuracy']
            ];
        }
        return $dots;
    }

    protected function getPolygonForAdd($lastPolygon = 0) {  // ,
        $polygons = [];
        $query = User_has_polygons::find()
                ->select(' `id`,`user_id`, AsText(`polygon`) as polygon ')
                ->where(' `game_id` = :idGame  and `timestamp` > :startTime and `timestamp` <= :stopTime   ')
                ->addParams([
                    ':idGame' => $this->idGame,
                    ':startTime' => $this->startTime,
                    ':stopTime' => $this->stopTime])
                ->orderBy(' `id` ASC ')
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $gamer = ( $value['user_id'] == $this->idGamer ) ? 'me' : 'opponent';
            $polygons[] = [
                'gamer' => $gamer,
                'id' => $value['id'],
                'arrDot' => $this->getDotsOfPolygon($value['polygon'])
            ];
        }
        return $polygons;
    }

    protected function getDotsOfPolygon($srtPolygon) {
        $arrDots = [];
        $arrMatches = [];
        $col = preg_match_all('/([0-9]{1,3}\.[0-9]+) ([0-9]{1,3}\.[0-9]+)/', $srtPolygon, $arrMatches);
        for ($i = 0; $i < $col; $i++) {
            $arrDots[] = [$arrMatches[1][$i], $arrMatches[2][$i]];
        }
        return $arrDots;
    }

    protected function getDotsForDelete($prevLastId = 0) {
        $deleteDots = [];
        $newLastId = $prevLastId;
        $query = Deleted_points::find()
                ->select(' `id`, `point_id` ')
                ->where(' `game_id` = :idGame AND `del_time` > :startTime and `del_time` <= :stopTime  ')
                ->addParams([
                    ':idGame' => $this->idGame,
                    ':startTime' => $this->startTime,
                    ':stopTime' => $this->stopTime])
                ->orderBy(' `id` ASC ')
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $deleteDots[] = ['id' => $value['point_id']];
        }
        return $deleteDots;
    }

    protected function getPolygonForDelete($prevLastId = 0) {

        $deletePolygon = [];
        $newLastId = $prevLastId;
        $query = Deleted_polygons::find()
                ->select(' id, polygon_id ')
                ->where(' `game_id` = :idGame AND `del_time` > :startTime and `del_time` <= :stopTime  ')
                ->addParams([
                    ':idGame' => $this->idGame,
                    ':startTime' => $this->startTime,
                    ':stopTime' => $this->stopTime])
                ->orderBy(' `id` ASC ')
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $deletePolygon[] = ['id' => $value['polygon_id']];
        }
        return $deletePolygon;
    }

}
