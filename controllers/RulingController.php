<?php

namespace app\controllers;

use app\models\User;
use Yii;
use app\controllers\BasisController;
use app\models\Game;

class RulingController extends BasisController {

    public function actionIndex() {
         $tt = Game::find()
              ->asArray()
                ->where( ' `user1_id` = :idGamer OR `user2_id`= :idGamer ' )
                ->addParams( [ ':idGamer' => 14 ] )
                ->orderBy(' `start_time` DESC ')
                ->limit(1)
                ->one();
      
        $query = [
            'idGame' => $_SESSION['idGame'],
            'idGamer' => $_SESSION['idGamer'],
            'idEnemy' => $_SESSION['idEnemy'],
            'startTime' => $_SESSION['startTime'],
            'queru' => $tt
        ];
         return $this->render('test', ['dots' => $query]);
    }

    // Ф-я обработки запроса ready.  ------------------------------------------------------------------------------------------
    // Входные данные,json: { 'latitude' : latitude , 'longitude' : longitude, 'accuracy' : accuracy , 'speed' : speed }
    // Создает ( или обновляет ) запись в таблице ready с передаными координатами . Возвращает json
    //  { 'opponent' : 0 , 'idGame' : 0 ,  'nick' : nicName ,
    //   'arrOpponents' : [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ] } 
    //   arrOpponents - массив потенциальных соперников, у которых есть статус ready .
    // Если в поле opponent_id записан id соперника - он возвращается в соот.поле ответа вместе с 
    // idGame. В сессии сохраняются $idGame, $idEnemy, $startTime - id игрока , его противника и 
    // стартовое время игры. Игра стартовала. Можно отправлять точки на обработку и получать данные
    public function actionGetReady() {

        // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);

        // Валидация переданых координат
        if (!$this->newPositionValidate($newPosition)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data']);
        }

        // Удаление записей в таблице REady, кот. давно обновлялись
        $this->deleteOldReady();

        // Создание новой записи в таблице `ready` или обновление координат, если запись есть
        // Принимает idGamer, возвращает данные поля opponent_id и idGame
        $request = $this->updateReady($newPosition);

        // Выборка всех игроков со статусом ready.        
        $request['arrOpponents'] = $this->getOpponents();

        $this->sendRequest($request);
    }

    // Ф-я отмены статуса ready -----------------------------------------------------------------------------------------------
    // Удаляет запись из таблицы ready пользователя с id, полученным из сессии, если у него еще
    // нет оппонента( т.е. поле `opponent_id` -  is null )
    public function actionStopReady() {

        // Удаление записи из таблицы `ready`
        $query = ' DELETE FROM `ready` WHERE `user_id` = :idGamer AND  `opponent_id` is null ';
        $col = Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => $this->idGamer])
                ->execute();
        $status = ( $col ) ? 'Ok' : 'error';
        $this->sendRequest(['status' => $status]);
    }

    // Ф-я выбора соперника для игры --------------------------------------------------------------------------------------
    // Получает id выбранного соперника. Если ни игрок ни его выбраный противник не имеют открытых
    //  игр , то в таблице ready им заполняется поле opponent_id, в таблице game создается новая игра
    //  Возвращает статус ok/error + message error.
    public function actionEnemySelection() {

        // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        $this->idEnemy = (int) $newPosition->idEnemy;

        // Проверка существования противника с таким id
        if (!$this->existenceUser($this->idEnemy)) {
            $this->sendRequest(['status' => 'error: idEnemy  does not exist ']);
        }

        // проверка на отсутствие открытых игр (своих и потенциального противника)
        if ($this->inGame($this->idGamer) || $this->inGame($this->idEnemy)) {
            $this->sendRequest(['status' => 'error: have unfinished games ']);
        }

        // Проверка на готовность противника с переданным id
        if (!$this->isReady($this->idEnemy) || !$this->isReady($this->idGamer)) {
            $this->sendRequest(['status' => 'error: idEnemy or  idGamer its not ready ']);
        }

        // Проверка не играем ли сам с собой
        if ($this->idEnemy == $this->idGamer) {
            $this->sendRequest(['status' => 'error: idEnemy ==  idGamer']);
        }

        // Добавление  id противников друг другу в таблицу ready
        $this->addIdEnemy($this->idEnemy, $this->idGamer);

        // Создание записи в таблице game  
        $this->addGame($this->idEnemy);

        $this->sendRequest(['status' => 'ok']);
    }

    // Ф-я завершения игры. Удаляет из таблицы ready строки игроков. Определяет победителя 
    // и корректирует таблицу game
    public function actionStopGame() {
        // Получение данных из сессии 
      //    $this->getSessVar();
      $this->getGameVar();

        // Проверка существования игры.
        if (!$this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy)) {
            $this->sendRequest(['status' => 'error', 'message' => ' error: access denied 2 ']);
        }

        // Удаление записей из таблицы ready
        $this->deleteReady($this->idGamer, $this->idEnemy);

        // определение победителя и обновление таблицы game
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

    // Ф-я получения рейтинга всех игроков. Возвращает массив :
    //[ { "username"=> nicName , "points"=> points } , ...  ] .
    public function getRating() {
        // Получение рейтинга всех игроков 
        $query = User::find()->select(' `username`,`points` ')->asArray()->all();
        return $query;
    }

    // Внутренние ф-ии ======================================================
    // Ф-я обновления/записи в таблицу ready статуса готов и координат юзера 
    // Принимает обьект с координатами. Возвращает данные поля `opponent_id` или 0,
    // если запись новая ( соперника пока нет ) и idGame, если `opponent_id` != 0
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

    // ф-я выбора всех игроков со статусом ready. Возвращает массив
    // [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ]
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

    // Ф-я записи данных новой игры в БД. Принимает idEnemy
    protected function addGame($idEnemy) {
        $query = new \app\models\Game();
        $query->user1_id = $this->idGamer;
        $query->user2_id = $idEnemy;
        $query->save();
    }

    // Ф-я проверки существования открытых игр для переданоого idGamer. Возвращает true/false.
    protected function inGame($idGamer) {
        $query = \app\models\Game::find()
                ->where('  (`user1_id` = :idGamer OR `user2_id`= :idGamer ) AND `winner_id` IS  NULL ')
                ->addParams([':idGamer' => $idGamer])
                ->count();
        return ( $query ) ? TRUE : FALSE;
    }

    // Ф-я проверки статуса ready у юзера с переданным id. Возвращает true/false.
    protected function isReady($idGamer) {
        $query = \app\models\Ready::find()
                ->where('  `user_id` = :idGamer AND `opponent_id` IS  NULL ')
                ->addParams([':idGamer' => $idGamer])
                ->count();
        return ( $query ) ? TRUE : FALSE;
    }

    // Ф-я добавление  id противников друг другу в таблицу ready
    protected function addIdEnemy($idEnemy, $idGamer) {
        $query = " UPDATE `ready` SET `opponent_id` = :idEnemy WHERE `user_id` = :idGamer  ";
        Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => (int) $idGamer, ':idEnemy' => (int) $idEnemy])
                ->execute();
        Yii::$app->db->createCommand($query)
                ->bindValues([':idGamer' => (int) $idEnemy, ':idEnemy' => (int) $idGamer])
                ->execute();
    }

    //Ф-я валидации новой позиции. Возвращает true/false.
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

    // Ф-ии метода stopGame ==========================================================
    // Ф-я определения победителя. Записывает id победителя в таблицу game. Принимает idGame.
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
        $query->points = (int) $query->points + $score1;
        $query->update();
        $query = User::findOne($idGamer2);
        $query->points = (int) $query->points + $score2;
        $query->update();
        return;
    }

    // Ф-я удаления записей из таблицы ready для участников игры
    protected function deleteReady($idGamer, $idEnemy) {
        Yii::$app->db->createCommand()->delete('ready', ['user_id' => $idGamer, 'opponent_id' => $idEnemy])->execute();
        Yii::$app->db->createCommand()->delete('ready', ['user_id' => $idEnemy, 'opponent_id' => $idGamer])->execute();
        return;
    }

    // Ф-я удаления записей ready, которые обновляли координаты более 12 минут назад.
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
