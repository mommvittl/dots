<?php

namespace app\controllers;


class TestController extends \yii\base\Controller{
    public function actionSetSessions() {
        $data = file_get_contents('php://input');
        $data = json_decode($data);
        $_SESSION['logg'] = TRUE;
        $_SESSION['idGamer'] = (int)$data->id;
        exit;
    }
}