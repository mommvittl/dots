<?php


namespace app\controllers;
use yii\web\Controller;

class SimulatorController  extends Controller{
    
    public function actionSimulator() {
       return $this->render('simulator');    
    }
    
    public function actionSimulator2() {
       return $this->render('simulator2');    
    }
}
