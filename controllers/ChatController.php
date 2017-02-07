<?php

namespace app\controllers;

use app\models\User;
use Yii;
use app\controllers\BasisController;
use app\models\Chat;

class ChatController extends BasisController {

    public function actionTest() {
        // 'viewUp', 'viewDn'
        $pr = json_encode(["lastMessageId" => 3, "firstMessageId" => 4, "functName" => 'viewUp']);
        $qr = json_decode($pr);
        $query = $this->readMessage($qr);

        return $this->render('test', ['dots' => $query]);
    }

    public function actionIndex() {
        return $this->render('chat');
    }

    public function actionNewMessage() {

        $strParameter = file_get_contents('php://input');
        $message = json_decode($strParameter);
        if (!$this->validateMessage($message)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data.']);
        }

        $this->writeMessage($message);



        $this->sendRequest(['status' => 'ok']);
    }

    public function actionGetMessage() {
        $strParameter = file_get_contents('php://input');
        $parameter = json_decode($strParameter);
        if (!$this->validateQuery($parameter)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data.']);
        }

        $arrMessage = $this->readMessage($parameter);

        $this->sendRequest(['status' => 'ok', 'arrMessage' => $arrMessage]);
    }

    //===========================================================================
    protected function validateMessage(&$mess) {
        if (!$mess && !is_object($mess)) {
            return FALSE;
        }
        if (!isset($mess->message)) {
            return FALSE;
        }
        $mess->message = filter_var($mess->message, FILTER_SANITIZE_STRING);
        return TRUE;
    }

    protected function writeMessage($mess) {
        $query = new Chat();
        $query->user_id = $this->idGamer;
        $query->message = $mess->message;
        $query->save();
        return;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    protected function validateQuery(&$param) {
        if (!$param && !is_object($param)) {
            return FALSE;
        }
        if (!isset($param->lastMessageId) && !isset($param->firstMessageId) && !isset($param->functName)) {
            return FALSE;
        }
        if (!in_array($param->functName, array('viewUp', 'viewDn'))) {
            return FALSE;
        }
        $param->firstMessageId = filter_var($param->firstMessageId, FILTER_SANITIZE_NUMBER_INT);
        $param->lastMessageId = filter_var($param->lastMessageId, FILTER_SANITIZE_NUMBER_INT);
        return TRUE;
    }

    protected function readMessage($param) {

        $idMess = ( $param->functName == 'viewUp' ) ? $param->firstMessageId : $param->lastMessageId;
        $where = ( $param->functName == 'viewUp' ) ? ' `chat`.`id` > :idMess' : ' `chat`.`id` < :idMess';
        if ($param->firstMessageId == 0) {
            $limit = 20;
            $orderBy = 'id DESC';
        } else {
            $limit = 3;
            $orderBy = ( $param->functName == 'viewUp' ) ? 'id ASC' : 'id DESC';
        }
        //   $limit = ( $param->firstMessageId == 0) ? 20 : 3;
        //    $orderBy = ( $param->functName == 'viewUp' ) ? 'id ASC' : 'id DESC';
        $query = Chat::find()
                ->select(' `chat`.`id`, `chat`.`data_post`, `chat`.`message`, `user`.`username`  ')
                ->innerJoin(' `user` ', ' `user`.`id` = `chat`.`user_id` ')
                ->where($where)
                ->addParams([':idMess' => $idMess])
                ->orderBy($orderBy)
                ->limit($limit)
                ->asArray()
                ->all();
        if ($param->firstMessageId == 0) {
          $query =  array_reverse( $query );
        }
        return $query;
    }

}
