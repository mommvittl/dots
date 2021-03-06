<?php

namespace app\controllers;

use Yii;
use app\models\Game;

ini_set('session.use_only_cookies', true);
if (!isset($_SESSION)) {
    session_start();
}

class BasisController extends \yii\base\Controller {

    protected $idGame = null;
    protected $idGamer = null;
    protected $idEnemy = null;
    protected $startTime = null;
    protected $queryPar = false;

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        if (!$this->loggout()) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: access denied 1 1 ']);
        }
    }

    protected function sendRequest($ajaxRequest) {
        header('Content-Type: text/json');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo( json_encode($ajaxRequest) );
        exit;
    }

    protected function loggout() {
        $this->idGamer = ( isset($_SESSION['__id']) ) ? (int) $_SESSION['__id'] : 0;
        if (!$this->idGamer) {
            return FALSE;
        }
        return $this->existenceUser($this->idGamer);
    }

    protected function existenceUser($idUser) {
        $query = 'SELECT count(*)  FROM `user` WHERE `id` = ' . $idUser;
        $col = Yii::$app->db->createCommand($query)->queryScalar();
        return ( $col ) ? TRUE : FALSE;
    }

    protected function existenceGame($idGame, $idGamer, $idEnemy) {
        $query = ' SELECT count(*) FROM `game` WHERE `id`= :idGame  and '
                . ' ( (  `user1_id`= :idGamer AND `user2_id` = :idEnemy ) OR '
                . ' ( `user1_id`= :idEnemy AND `user2_id` = :idGamer ) ) ';
        $col = Yii::$app->db->createCommand($query)
                ->bindValues([':idGame' => $idGame, ':idGamer' => $idGamer, ':idEnemy' => $idEnemy])
                ->queryScalar();
        return ( $col ) ? TRUE : FALSE;
    }

    protected function getStatusGame() {
        $query = \app\models\Game::findOne($this->idGame);
        if (is_null($query->winner_id)) {
            $status = TRUE;
            $winner = null;
        } else {
            $status = FALSE;
            if ($query->winner_id == 0) {
                $winner = 'draw';
            } else {
                $winner = ( $query->winner_id == $this->idGamer ) ? 'me' : 'opponent';
            }
        }
        if ($query->user1_id == $this->idGamer) {
            $scoresMe = $query->user1_scores;
            $scoresEnemy = $query->user2_scores;
        } else {
            $scoresMe = $query->user2_scores;
            $scoresEnemy = $query->user1_scores;
        }

        return ['statusGame' => $status,
            'winner' => $winner,
            'scoresMe' => $scoresMe,
            'scoresEnemy' => $scoresEnemy];
    }

    protected function getQueryParam() {
        $strParameter = file_get_contents('php://input');
        $queryParam = json_decode($strParameter);
        return $queryParam;
    }

    protected function getGameVar() {
        $query = Game::find()
                ->where(' `user1_id` = :idGamer OR `user2_id`= :idGamer ')
                ->addParams([':idGamer' => $this->idGamer])
                ->orderBy(' `start_time` DESC ')
                ->limit(1)
                ->asArray()
                ->one();
        if ($query) {
            $this->idGame = $query['id'];
            $this->startTime = $query['start_time'];
            $this->idEnemy = ( $this->idGamer == $query['user1_id'] ) ? $query['user2_id'] : $query['user1_id'];
        }
        return;
    }

}
