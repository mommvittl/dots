<?php

namespace app\controllers;

use Yii;
use app\models\User_has_points;
use app\models\User_has_polygons;
use app\models\Deleted_points;
use app\models\Deleted_polygons;
use app\controllers\BasisController;

class RoundController extends BasisController {

    protected $arrAddDots = [];
    protected $arrAddPolygon = [];
    protected $arrIdDeleteDots = [];
    protected $arrIdDeletePolygon = [];
    protected $lastDelDotId = 0;
    protected $lastDelPolygonId = 0;
    protected $scores = 0; // Заработанные в этом вызове очки

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->getGameVar();
        if (!$this->validateSessVar()) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: access denied 2 2 ']);
        }
    }

    public function actionChangePosition() {
        $startTime = microtime(true);
        $statusGame = $this->getStatusGame();
        if ($statusGame['statusGame'] === FALSE) {
            $this->sendRequest(['status' => 'error', 'message' => 'gameOver.']);
        }     
        if ($this->isTimeOut($this->startTime)) {
            $statusGameOver = $this->gameOver();
            $this->sendRequest(['status' => 'error', 'message' => 'TimeOut.']);
        }
        $strParameter = file_get_contents('php://input');
        $arrNewPosition = json_decode($strParameter);

        if (!is_array($arrNewPosition)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data. Mast be array.']);
        }
        $request = [];
        $len = count($arrNewPosition);
        for ($i = 0; $i < $len; $i++) {
            $request[] = $this->gameProcess($arrNewPosition[$i]);
        }
        $request[] = ['time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'time2' => microtime(true) - $startTime];
        $this->sendRequest($request);
    }

    // ==================================================
    protected function gameProcess($newPosition) {
         $t1 =  microtime(true);
        if (!$this->newPositionValidate($newPosition)) {
            return( ['status' => 'error', 'message' => 'error: incorrect input data'] );
        }
        if ($this->inPolygons($newPosition)) {
            $this->cutAllDots($this->idGamer);
            return( ['status' => 'ok'] );
        }
        $repeat = $this->repeatVisit($newPosition);
        if ($repeat === false) {
            $idNewDot = $this->addDot($newPosition);
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
            return( ['status' => 'ok' ] );
        }
        $possiblePoligon = $this->getPossiblePoligon($repeat);
        if (!$possiblePoligon || !is_array($possiblePoligon)) {
            return( ['status' => 'error', 'message' => 'error message 1'] );
        }
        $tailLen = count($possiblePoligon);
        if ($tailLen <= 7) {
             $this->cutDots( $this->idGamer, $repeat, '>=' ) ;
            $idNewDot = $this->addDot($newPosition);
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
            return( ['status' => 'ok'] );
        } else {
            $idNewPolygon = $this->addPolygon($possiblePoligon);
            $this->cutAllDots($this->idGamer);
            $enemyDotsInPolygon = $this->getDotsInPolygon($this->idEnemy, $idNewPolygon);
            if ($enemyDotsInPolygon !== false) {
                  $this->cutDots( $this->idEnemy, $enemyDotsInPolygon, '<=' ) ;
            }
            $arrPolyInPolygon = $this->getPolyInPolygon($this->idEnemy, $idNewPolygon);
            if ($arrPolyInPolygon !== false) {
                $this->delPoligonById($arrPolyInPolygon);
            }
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
          return( ['status' => 'ok'] );
        }
    }

    // --------------------------------------------------------------------------------------------------------------------------------------
    public function actionGetChange() {
        $startTime = microtime(true);
 
        $statusGame = $this->getStatusGame();
        if ($statusGame['statusGame'] === FALSE) {
            $this->sendRequest(['status' => 'gameOver', 'message' => $statusGame]);
        }
        $strParameter = file_get_contents('php://input');
        $parameterQuery = $this->getParameterQuery($strParameter);
        if ($parameterQuery === FALSE) {
            $this->sendRequest(['status' => 'error', 'message' => 'incorrect input data']);
        }
        $this->arrAddDots = $this->getDotsForAdd($parameterQuery['lastDotId']);
        $this->arrAddPolygon = $this->getPolygonForAdd($parameterQuery['lastPolygonId']);
        list( $this->lastDelDotId, $this->arrIdDeleteDots ) = $this->getDotsForDelete($parameterQuery['lastDelDotId']);
        list( $this->lastDelPolygonId, $this->arrIdDeletePolygon ) = $this->getPolygonForDelete($parameterQuery['lastDelPolygonId']);

        $request = [
            'status' => 'ok',
            'arrAddDots' => $this->arrAddDots,
            'arrAddPolygon' => $this->arrAddPolygon,
            'arrIdDeleteDots' => $this->arrIdDeleteDots,
            'arrIdDeletePolygon' => $this->arrIdDeletePolygon,
            'lastDelDotId' => $this->lastDelDotId,
            'lastDelPolygonId' => $this->lastDelPolygonId,
            'myScores' => $statusGame['scoresMe'],
            'enemyScores' => $statusGame['scoresEnemy'],
            'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'time2' => microtime(true) - $startTime
        ];
        $this->sendRequest($request);
    }

    //===================================================
    protected function validateSessVar() {
        if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $this->startTime)) {
            return FALSE;
        }
        if (!$this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy)) {
            return FALSE;
        }
        if (!$this->existenceUser($this->idEnemy)) {
            return FALSE;
        }
        return TRUE;
    }
    protected function newPositionValidate($position) {

        if (!is_object($position)) {
            return FALSE;
        }
        if (filter_var($position->latitude, FILTER_VALIDATE_FLOAT) === false) {
            return FALSE;
        };
        if (filter_var($position->longitude, FILTER_VALIDATE_FLOAT) === false) {
            return FALSE;
        };
        if (filter_var($position->accuracy, FILTER_VALIDATE_INT) === false) {
            return FALSE;
        };
        if (($position->latitude <= 0) || ($position->latitude >= 180 )) {
            return FALSE;
        };
        if (( $position->longitude <= 0 ) || ( $position->longitude >= 90 )) {
            return FALSE;
        };
        return TRUE;
    }
    protected function addDot($position) {

        $query = ' INSERT INTO `user_has_points`  SET `user_id` = ' . $this->idGamer;
        $query .= ' ,  `accuracy` = ' . $position->accuracy . ' , `game_id` = ' . $this->idGame;
        $query .= ', `point` = PointFromText( "POINT( ' . $position->latitude . ' ' . $position->longitude . ' )"  ) ';
        $query .= ', `status` = 1 ';
        Yii::$app->db->createCommand($query)->execute();
        $idNewDot = Yii::$app->db->createCommand(' SELECT LAST_INSERT_ID() ')->queryOne();

        $this->scores++; 
        return $idNewDot;
    }
    protected function addPolygon($arrDotsForPolygon) {
        $len = count($arrDotsForPolygon);
        $strCoordinates = '';
        for ($i = 0; $i < $len; $i++) {
            $strCoordinates .= ' ' . $arrDotsForPolygon[$i]['x'] . ' ' . $arrDotsForPolygon[$i]['y'] . ' , ';
        }
        $strCoordinates .= ' ' . $arrDotsForPolygon[0]['x'] . ' ' . $arrDotsForPolygon[0]['y'] . ' ';
        $query = ' INSERT INTO `user_has_polygons` SET `user_id` =  ' . $this->idGamer;
        $query .= ' , `status` = 1,  `game_id` =  ' . $this->idGame;
        $query .= ' , `polygon` = PolygonFromText( " POLYGON( ( ' . $strCoordinates . ' ) ) " )  ';
        Yii::$app->db->createCommand($query)->execute();
        $idNewDot = Yii::$app->db->createCommand(' SELECT LAST_INSERT_ID() as i ')->queryOne();
        return $idNewDot['i'];
    }
    protected function inPolygons($position) {
        $query = ' SELECT count(*) as col FROM `user_has_polygons` '
                . 'WHERE   `status` = 1 AND `game_id` = ' . $this->idGame
                . ' AND ST_Within( PointFromText( "POINT( ' . $position->latitude . " " . $position->longitude
                . ' )" ) , `polygon` ) = 1  ';
        $col = Yii::$app->db->createCommand($query)->queryOne();
        return ( $col['col'] ) ? TRUE : FALSE;
    }

    protected function cutDots( $idGamer, $idDot, $condition ){
        $strQuery = ' INSERT INTO `deleted_points` ( `point_id`,`game_id` ) '
                . 'SELECT `id`,`game_id` FROM `user_has_points` WHERE `user_id` = :idGamer '
                . ' AND  `game_id` = :idGame AND `status` = 1 AND `id` ' . $condition . ' :idDot  ; '
                . ' UPDATE `user_has_points` SET `status` = 0 WHERE  `user_id` = :idGamer '
                . ' AND  `game_id` = :idGame AND `status` = 1 AND `id` ' . $condition . ' :idDot  ' ;             
        Yii::$app->db->createCommand( $strQuery  )
                ->bindValues( [ ':idGamer' => $idGamer , ':idGame' => $this->idGame , ':idDot' => $idDot ] )
                ->execute();
        return ;        
    }
    
    protected function cutAllDots( $idGamer ) {
        $strQuery = ' INSERT INTO `deleted_points` ( `point_id`,`game_id` ) '
                . 'SELECT `id`,`game_id` FROM `user_has_points` WHERE `user_id` = :idGamer '
                . ' AND  `game_id` = :idGame AND `status` = 1 ;  UPDATE `user_has_points` '
                . 'SET `status` = 0 WHERE  `user_id` = :idGamer  AND  `game_id` = :idGame AND `status` = 1   ' ;
        Yii::$app->db->createCommand( $strQuery  )
                ->bindValues( [ ':idGamer' => $idGamer , ':idGame' => $this->idGame ] )
                ->execute();
        return ; 
    }
  
    protected function repeatVisit($position) {
        $dist = ( $position->accuracy > 20 ) ? $position->accuracy : 20;
        if ($dist > 40) {
            $dist = 40;
        }
        $radiusAccuracy = 0.0000075 * $dist;
        $strQuery = " SELECT `id` FROM `user_has_points` WHERE `game_id`= " . $this->idGame
                . " AND `user_id`=" . $this->idGamer . " AND  `status`='1'  AND  "
                . " ST_Distance( `point`, PointFromText('POINT(" . $position->latitude . " " . $position->longitude . ")')) "
                . " < " . $radiusAccuracy . " ORDER BY 'id' LIMIT 1 ";

        $query = Yii::$app->db->createCommand($strQuery)->queryScalar();
        return ( $query === FALSE) ? FALSE : $query;
    }
    protected function getPossiblePoligon($idDot = 0) {
        $strQuery = "SELECT `id`, `user_id`, X( `point` ) as x, Y( `point` ) as y, `accuracy`, `timestamp`, `game_id` "
                . "  FROM `user_has_points` WHERE "
                . " `id` >= " . $idDot . " AND `status` = '1' AND `game_id` = " . $this->idGame
                . " AND `user_id` = " . $this->idGamer;
        $query = Yii::$app->db->createCommand($strQuery)->queryAll();
        return $query;
    }
    protected function getDotsInPolygon($idGamer, $idPolygon) {
        $query = User_has_points::find()
                        ->select(' u.`id` ')
                        ->from(' `user_has_points` as u, `user_has_polygons` as p  ')
                        ->where(' u.`user_id` = :idGamer and u.`game_id` = :idGame and u.`status` = 1 '
                                . ' and p.`id` = :idPolygon and ST_Within( u.`point`, p.`polygon` ) = 1 ')
                        ->addParams([':idGame' => $this->idGame, ':idGamer' => $idGamer, ':idPolygon' => $idPolygon])
                        ->orderBy('id DESC')->asArray()->all();
        if ($query && is_array($query)) {
            $this->scores += count($query) * 10;
            return $query[0]['id'];
        } else {
            return FALSE;
        }
    }
    protected function getPolyInPolygon($idGamer, $idPolygon) {
        $query = User_has_polygons::find()
                        ->select(' `id` ')
                        ->where(' `status` = 1 AND `game_id` = :idGame AND '
                                . 'ST_Within( `polygon` , '
                                . ' ( SELECT `polygon` FROM `user_has_polygons` WHERE `id` = :idPolygon ) ) = 1'
                                . ' AND `id` != :idPolygon ')
                        ->addParams([':idGame' => $this->idGame, ':idPolygon' => $idPolygon])
                        ->asArray()->all();
        return $query;
        return ( $query && is_array($query) ) ? $query : false;
    }

    protected function delPoligonById($arrIdPoligons) {
        $len = count($arrIdPoligons);
        for ($i = 0; $i < $len; $i++) {
            $query = User_has_polygons::findOne($arrIdPoligons[$i]['id']);
            if ($query->user_id == $this->idEnemy) {
                $this->scores += 100;
            }
            $query->status = '0';
            $query->update();
            $delPoly = new \app\models\Deleted_polygons;
            $delPoly->game_id = $this->idGame;
            $delPoly->polygon_id = $arrIdPoligons[$i]['id'];
            $delPoly->save();
        }
    }
    protected function addScores($idGame, $idGamer, $scores) {
        $query = \app\models\Game::findOne($idGame);
        $columnName = ( (int) $query->user1_id == (int) $idGamer ) ? 'user1_scores' : 'user2_scores';
        $query->$columnName += $scores;
        $query->update();

        return;
    }
    protected function isTimeOut($startTimeStr) {
        $dt = \DateTime::createFromFormat("Y-m-d H:i:s", $startTimeStr);
        $dt2 = new \DateTime();
        if (!is_object($dt2) || !is_object($dt)) {
            return FALSE;
        }
        $time = $dt2->getTimestamp() - $dt->getTimestamp();    
        return ( $time > 113900 ) ? TRUE : FALSE;
        return FALSE;
    }
    protected function gameOver() {
        Yii::$app->runAction('ruling/stop-game');
        return;
    }

    //====================================================
    protected function getDotsForAdd($lastDot = 0) {
        $dots = [];
        $query = User_has_points::find()
                ->select(" `id`, `user_id`, X( `point` ), Y( `point` ), `accuracy`, `timestamp` ")
                ->where(' `id` > :id and `game_id` = :idGame  and `status` = 1 ')
                ->addParams([':id' => $lastDot, ':idGame' => $this->idGame])
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
                ->select(' `id`,`user_id`, AsText(`polygon`) as polygon, `timestamp` ')
                ->where(' `id` > :id and `game_id` = :idGame  and `status` = 1  ')
                ->addParams([':id' => $lastPolygon, ':idGame' => $this->idGame])
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
                ->select(' `id`, `point_id` ')->where('id > :id and game_id = :idGame')
                ->addParams([':id' => $prevLastId, ':idGame' => $this->idGame])
                ->orderBy(' `id` ASC ')
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $deleteDots[] = ['id' => $value['point_id']];
            $newLastId = $value['id'];
        }
        return [$newLastId, $deleteDots];
    }
    protected function getPolygonForDelete($prevLastId = 0) {

        $deletePolygon = [];
        $newLastId = $prevLastId;
        $query = Deleted_polygons::find()
                ->select(' id, polygon_id ')
                ->where('id > :id and game_id = :idGame')
                ->addParams([':id' => $prevLastId, ':idGame' => $this->idGame])
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $deletePolygon[] = ['id' => $value['polygon_id']];
            $newLastId = $value['id'];
        }
        return [$newLastId, $deletePolygon];
    }

    protected function getParameterQuery($strQuery) {
        $query = json_decode($strQuery, true);
        if (!is_array($query) || !isset($query['lastDotId']) || !isset($query['lastPolygonId']) || !isset($query['lastDelDotId']) || !isset($query['lastDelPolygonId'])) {
            return FALSE;
        }
        return [
            'lastDotId' => (int) $query['lastDotId'],
            'lastPolygonId' => (int) $query['lastPolygonId'],
            'lastDelDotId' => (int) $query['lastDelDotId'],
            'lastDelPolygonId' => (int) $query['lastDelPolygonId']
        ];
    }

}
