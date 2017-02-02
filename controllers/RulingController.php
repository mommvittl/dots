<?php

namespace app\controllers;

use app\models\User;
use Yii;
use app\controllers\BasisController;
use app\models\Game;

class RulingController extends BasisController {

    public function actionIndex() {

  /*
          phpinfo();
        $query = [
            'idGame' => $this->idGame,
            'idGamer' =>$this->idGamer,
            'idEnemy' => $this->idEnemy,
            'startTime' => $this->startTime

        ];
        return $this->render('test', ['dots' => $query]);*/
    }
    public function actionGetReady() {
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        if (!$this->newPositionValidate($newPosition)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data']);
        }
        $this->deleteOldReady();
        $request = $this->updateReady($newPosition);
        $request['arrOpponents'] = $this->getOpponents();
        $this->sendRequest($request);
    }
    public function actionStopReady() {
        $query = ' DELETE FROM `ready` WHERE `user_id` = :idGamer AND  `opponent_id` is null ';
        $col = Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => $this->idGamer])
                ->execute();
        $status = ( $col ) ? 'Ok' : 'error';
        $this->sendRequest(['status' => $status]);
    }
    public function actionEnemySelection() {
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        $this->idEnemy = (int) $newPosition->idEnemy;
        if (!$this->existenceUser($this->idEnemy)) {
            $this->sendRequest(['status' => 'error: idEnemy  does not exist ']);
        }
        if ($this->inGame($this->idGamer) || $this->inGame($this->idEnemy)) {
            $this->sendRequest(['status' => 'error: have unfinished games ']);
        }
        if (!$this->isReady($this->idEnemy) || !$this->isReady($this->idGamer)) {
            $this->sendRequest(['status' => 'error: idEnemy or  idGamer its not ready ']);
        }
        if ($this->idEnemy == $this->idGamer) {
            $this->sendRequest(['status' => 'error: idEnemy ==  idGamer']);
        }
        $this->addIdEnemy($this->idEnemy, $this->idGamer);
        $this->addGame($this->idEnemy);

        $this->sendRequest(['status' => 'ok']);
    }
    public function actionStopGame() {
        $this->getGameVar();
        if (!$this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy)) {
            $this->sendRequest(['status' => 'error', 'message' => ' error: access denied 2 ']);
        }
        $this->deleteReady($this->idGamer, $this->idEnemy);
        $this->getWinner($this->idGame);
        $this->sendRequest(['status' => 'ok']);
    }

    public function actionRemoveSession() {
        if (isset($_SESSION['idEnemy'])) {
            unset($_SESSION['idEnemy']);
        }
        if (isset($_SESSION['idGame'])) {
            unset($_SESSION['idGame']);
        }
        if (isset($_SESSION['startTime'])) {
            unset($_SESSION['startTime']);
        }
        $this->sendRequest(['status' => 'ok']);
    }
    //======================================================
    protected function updateReady($position) {
        $idEnemy = 0;
        $idGame = 0;
        $enemyNic = '';
        $updateTime = new \DateTime();
        $row = \app\models\Ready::find()
                ->where(' `user_id` = :idGamer ')
                ->addParams([':idGamer' => $this->idGamer])
                ->one();
        if (!$row && !is_object($row)) {
            $query = ' INSERT INTO `ready` SET `user_id` = :idGamer ';
            $query .= ' , `point` = PointFromText("POINT( ' . $position->latitude . ' ' . $position->longitude . ' )") ';
            $query .= ' , `update_time` =   :time ';
            Yii::$app->db->createCommand($query)
                    ->bindValues([':idGamer' => $this->idGamer, ':time' => $updateTime->format('Y-m-d H:i:s')])
                    ->execute();
        } else {
            // Обновление координат в БД
            $query = ' UPDATE `ready` SET '
                    . ' `point` = PointFromText("POINT( ' . $position->latitude . ' ' . $position->longitude . ' )")  '
                    . ' , `update_time` = :time  WHERE `user_id` = :idGamer ';
            Yii::$app->db->createCommand($query)
                    ->bindValues([':idGamer' => $this->idGamer, ':time' => $updateTime->format('Y-m-d H:i:s')])
                    ->execute();
            // Если есть оппонент - передает idGame
            if ($row->opponent_id) {
                $idEnemy = (int) $row->opponent_id;
                $query = \app\models\Game::find()
                        ->select(' `id`, `start_time` ')
                        ->where(' (`user1_id` = :idGamer OR `user2_id`= :idGamer ) AND `winner_id` is NULL')
                        ->addParams([':idGamer' => $this->idGamer])
                        ->one();
                $idGame = $query->id;
                $startTime = $query->start_time;
                $query = 'SELECT `username` FROM `user` WHERE `id` = :idEnemy ';
                $enemyNic = Yii::$app->db->createCommand($query)
                        ->bindValues([':idEnemy' => $idEnemy])
                        ->queryScalar();
                // Загружаем в сессию данные о игре
                $_SESSION['idGame'] = $idGame;
                $_SESSION['idEnemy'] = $idEnemy;
                $_SESSION['startTime'] = $startTime;
            }
        }
        return ['opponent' => $idEnemy, 'idGame' => $idGame, 'enemyNic' => $enemyNic];
    }
    protected function getOpponents() {
        $arrOpponents = [];
        $query = 'SELECT `user_id`, X(`point`) as x, Y(`point`) as y, u.`username`  FROM `ready`, '
                . ' `user` as u   WHERE `opponent_id` is null AND `user_id` = u.`id` '
                . 'AND `user_id` != :idGamer ';
        $row = Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => $this->idGamer])
                ->queryAll();
        foreach ($row as $value) {
            $arrOpponents[] = [
                'id' => $value['user_id'],
                'nick' => $value['username'],
                'latitude' => $value['x'],
                'longitude' => $value['y']
            ];
        }
        return $arrOpponents;
    }
    protected function addGame($idEnemy) {
        $query = new \app\models\Game();
        $query->user1_id = $this->idGamer;
        $query->user2_id = $idEnemy;
        $query->save();
    }
    protected function inGame($idGamer) {
        $query = \app\models\Game::find()
                ->where('  (`user1_id` = :idGamer OR `user2_id`= :idGamer ) AND `winner_id` IS  NULL ')
                ->addParams([':idGamer' => $idGamer])
                ->count();
        return ( $query ) ? TRUE : FALSE;
    }
    protected function isReady($idGamer) {
        $query = \app\models\Ready::find()
                ->where('  `user_id` = :idGamer AND `opponent_id` IS  NULL ')
                ->addParams([':idGamer' => $idGamer])
                ->count();
        return ( $query ) ? TRUE : FALSE;
    }
    protected function addIdEnemy($idEnemy, $idGamer) {
        $query = " UPDATE `ready` SET `opponent_id` = :idEnemy WHERE `user_id` = :idGamer  ";
        Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => (int) $idGamer, ':idEnemy' => (int) $idEnemy])
                ->execute();
        Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => (int) $idEnemy, ':idEnemy' => (int) $idGamer])
                ->execute();
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
        if (($position->latitude <= 0) || ($position->latitude >= 180 )) {
            return FALSE;
        };
        if (( $position->longitude <= 0 ) || ( $position->longitude >= 90 )) {
            return FALSE;
        };
        return TRUE;
    }
    protected function getWinner($idGame) {
        $query = \app\models\Game::findOne((int) $idGame);
        $idGamer1 = (int) $query->user1_id;
        $idGamer2 = (int) $query->user2_id;
        $score1 = ( $query->user1_scores ) ? (int) $query->user1_scores : 0;
        $score2 = ( $query->user2_scores ) ? (int) $query->user2_scores : 0;
        if ($score1 != $score2) {
            $winner = ( $score1 > $score2 ) ? $query->user1_id : $query->user2_id;
        } else {
            $winner = 0;
        }
        $query->winner_id = $winner;
        $query->update();

        $query = User::findOne($idGamer1);
        $query->scores = (int)$query->scores + $score1;
        $query->update();
        $query = User::findOne($idGamer2);
        $query->scores = (int)$query->scores + $score2;
        $query->update();
        return;
    }
    protected function deleteReady($idGamer, $idEnemy) {
        Yii::$app->db->createCommand()->delete('ready', ['user_id' => $idGamer, 'opponent_id' => $idEnemy])->execute();
        Yii::$app->db->createCommand()->delete('ready', ['user_id' => $idEnemy, 'opponent_id' => $idGamer])->execute();
        return;
    }
    protected function deleteOldReady() {
        $updateTime = new \DateTime();
        $delTime = $updateTime->modify('-12 minutes')->format("Y-m-d H:i:s");
        Yii::$app->db->createCommand()
                ->delete('ready', ' `opponent_id` is NULL and `update_time` < :time  ')
                ->bindParam(':time', $delTime)
                ->execute();
        return;
    }

}
