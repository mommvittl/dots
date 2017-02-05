<?php

namespace app\controllers;

use app\models\Game;

class HistoryController extends BasisController {

    public function actionIndex() {
        
    }

    public function actionHistory() {
        $strParameter = file_get_contents('php://input');
        $newTiming = json_decode($strParameter, TRUE);

        if (!$this->timingValidate($newTiming)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data']);
        }


        $this->sendRequest(['status' => 'ok']);
    }

    //=============================================================================
    protected function timingValidate($param) {
        if (!is_array($param) || !isset($param['idGame']) || !isset($param['idGamer']) || !isset($param['idEnemy']) || !isset($param['oldTime']) || !isset($param['newTime'])) {
            return FALSE;
        }
        $this->idGame = (int) $param['idGame'];
        $this->idGamer = (int) $param['idGamer'];
        $this->idEnemy = (int) $param['idEnemy'];
        if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $param['oldTime'])) {
            return FALSE;
        }
        if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $param['newTime'])) {
            return FALSE;
        }
         return $this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy);
    }



}
