<?php

namespace app\controllers;

class HistorygameController extends BasisController {

    public function actionIndex() {
          $strParameter = file_get_contents('php://input');
         $newTiming = json_decode($strParameter, TRUE );
        // print_r($newTiming);
       // var_dump( $newTiming );
        $this->sendRequest(['status' => 'ok' , 'test' => $strParameter ]);
    }

}
