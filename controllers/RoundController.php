<?php

namespace app\controllers;

use Yii;
use app\models\User_has_points;
use app\models\User_has_polygons;
use app\models\Deleted_points;
use app\models\Deleted_polygons;
use app\controllers\RulingController;

ini_set('session.use_only_cookies', true);
//session_start();

if (!isset($_SESSION)) { session_start(); }

class RoundController extends \yii\base\Controller {

    protected $idGame = null;
    protected $idGamer = null;
    protected $idEnemy = null;
    protected $startTime = null;
    protected $arrAddDots = [];
    protected $arrAddPolygon = [];
    protected $arrIdDeleteDots = [];
    protected $arrIdDeletePolygon = [];
    protected $lastDelDotId = 0;
    protected $lastDelPolygonId = 0;
    protected $scores = 0; // Заработанные в этом вызове очки
    protected $session;

    // Временный метод !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    public function actionIndex() {
        $_SESSION[ 'idGame' ] = 12;
        $_SESSION[ 'idGamer' ] = 19;
        $_SESSION[ 'idEnemy' ] = 12;
        $_SESSION[ 'startTime' ] = '2017-01-27 17:58:26';
        $query = 44;
        return $this->render('test', ['dots' => $_SESSION ]);
    }

    // Конец временного метода. Удалить !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    // ТЕстовы метод ----------------------------------------------------------
    public function actionTest() {
        $strTestParameter = file_get_contents('php://input');
        $arrTestPosition = json_decode($strTestParameter);
        if (!is_array($arrTestPosition)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data. Mast be array.']);
        }
        $len = count($arrTestPosition);
        for ($i = 0; $i < $len; $i++) {
            $newDot = $arrTestPosition[$i];
            $query = ' INSERT INTO `user_has_points`  SET `user_id` = 333 ';
            $query .= ' ,  `accuracy` = ' . $newDot->accuracy . ' , `game_id` = 3333 ';
            $query .= ', `point` = PointFromText( "POINT( ' . $newDot->latitude . ' ' . $newDot->longitude . ' )"  ) ';
            $query .= ', `status` = 1 ';
            Yii::$app->db->createCommand($query)->execute();
        }
        $this->sendRequest(['status' => 'ok', 'message' => 'test']);
    }

    // Ф-я обработки игрового процесса. ---------------------------------------------------------------------------------------  
    // Ф-я обработки игрового процесса. ---------------------------------------------------------------------------------------
    public function actionChangePosition() {
        $this->session = Yii::$app->session;
        if (!$this->session->isActive) {
              $this->sendRequest(['status' => 'error', 'message' => 'error: the session is not open ']);
        }
        // Проверка залогинен ли юзер
        if (!$this->loggout()) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: access denied 1 1 ']);
        }

        // Проверка состояния игры. Если игра закончена - возвращаем статус gameOver
        $statusGame = $this->getStatusGame();
        if ($statusGame['statusGame'] === FALSE) {
            $this->sendRequest(['status' => 'error', 'message' => 'gameOver.']);
        }

        // Проверка на таймаут. Если время игры закончено - закрываем игру.
        if ($this->isTimeOut($this->startTime)) {
            $statusGameOver = $this->gameOver();
            $this->sendRequest(['status' => 'error', 'message' => 'TimeOut.']);
        }


        // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $arrNewPosition = json_decode($strParameter);

        if (!is_array($arrNewPosition)) {
            $this->sendRequest(['status' => 'error', 'message' => 'error: incorrect input data. Mast be array.']);
        }

        // Обрабока переданных точек
        $request = [];
        $len = count($arrNewPosition);
        for ($i = 0; $i < $len; $i++) {
            $request[] = $this->gameProcess($arrNewPosition[$i]);
        }
        $this->sendRequest($request);
    }

    // ============ Ф-я обработки игрового процесса ======================================
    protected function gameProcess($newPosition) {

        // Проверка валидности новых данных
        if (!$this->newPositionValidate($newPosition)) {
            return( ['status' => 'error', 'message' => 'error: incorrect input data'] );
        }

        //Проверка новой точки на попадание в полигон    
        if ($this->inPolygons($newPosition)) {
            // Если попали в полигон - обрезаем хвост
            $this->cutTail($this->idGamer);
            return( ['status' => 'ok'] );
        }

        // Проверка на повторное посещение точки
        // В $repeat - id точки из хвоста в которой произошло повтор
        $repeat = $this->repeatVisit($newPosition);

        if ($repeat === false) {
            // точка не посещалась - сохраняем новую точку.
            $idNewDot = $this->addDot($newPosition);
            // добвляем игроку очки в БД
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
            return( ['status' => 'ok'] );
        }
        // Повторное посещение. Получаем массив обьектов точек ( потенциального полигона )
        // для анализа на дальнейшие действия
        $possiblePoligon = $this->getPossiblePoligon($repeat);
        if (!$possiblePoligon || !is_array($possiblePoligon)) {
            return( ['status' => 'error', 'message' => 'error message 1'] );
        }

        // Получаем длинну хвоста
        $tailLen = count($possiblePoligon);

        if ($tailLen <= 7) {
            // Длинна кольца слишком мала - стираем головные точки 
            $this->cutTail($this->idGamer, $repeat, FALSE, TRUE);
            // Добавляем новую позицию в БД
            $idNewDot = $this->addDot($newPosition);
            // добвляем игроку очки в БД
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
            return( ['status' => 'ok'] );
        } else {
            // Длинна кольца достаточна - формируем полигон
            $idNewPolygon = $this->addPolygon($possiblePoligon);
            // Удаляем все точки
            $this->cutTail($this->idGamer);
            // Ищем точки противника попавшие в новосозданный полигон
            $enemyDotsInPolygon = $this->getDotsInPolygon($this->idEnemy, $idNewPolygon);
            // Отрезаем хвост противнику
            if ($enemyDotsInPolygon !== false) {
                $this->cutTail($this->idEnemy, $enemyDotsInPolygon, TRUE);
            }
            // Ищем полигоны внутри нашего полигона
            $arrPolyInPolygon = $this->getPolyInPolygon($this->idEnemy, $idNewPolygon);
            //Если нашли - удаляем 
            if ($arrPolyInPolygon !== false) {
                $this->delPoligonById($arrPolyInPolygon);
            }
            // добвляем игроку очки в БД
            $this->addScores($this->idGame, $this->idGamer, $this->scores);
            return( ['status' => 'ok'] );
        }
    }

    // Ф-я прердачи на браузер изменений состояния точек ----------------------------------------------------------------
    public function actionGetChange() {
        $this->session = Yii::$app->session;
        if (!$this->session->isActive) {
              $this->sendRequest(['status' => 'error', 'message' => 'error: the session is not open ']);
        }
        
        // Проверка залогинен ли юзер
        if (!$this->loggout()) {
            $this->sendRequest([
                'status' => 'error',
                'message' => 'error: access denied'
            ]);
        }

        // Проверка состояния игры. Если игра закончена - возвращаем статус gameOver
        $statusGame = $this->getStatusGame();
        if ($statusGame['statusGame'] === FALSE) {
            $this->sendRequest([
                'status' => 'gameOver',
                'message' => $statusGame
            ]);
        }

        // Создание нового обьекта с параметрами запроса
        $strParameter = file_get_contents('php://input');
        $parameterQuery = json_decode($strParameter);

        // Выбор данных для передачи на отрисовку  
        $this->arrAddDots = $this->getDotsForAdd($parameterQuery->lastDotId);
        $this->arrAddPolygon = $this->getPolygonForAdd($parameterQuery->lastPolygonId);
        list( $this->lastDelDotId, $this->arrIdDeleteDots ) = $this->getDotsForDelete($parameterQuery->lastDelDotId);
        list( $this->lastDelPolygonId, $this->arrIdDeletePolygon ) = $this->getPolygonForDelete($parameterQuery->lastDelPolygonId);

        // формирование ответа для браузера
        $request = [
            'status' => 'ok',
            'arrAddDots' => $this->arrAddDots,
            'arrAddPolygon' => $this->arrAddPolygon,
            'arrIdDeleteDots' => $this->arrIdDeleteDots,
            'arrIdDeletePolygon' => $this->arrIdDeletePolygon,
            'lastDelDotId' => $this->lastDelDotId,
            'lastDelPolygonId' => $this->lastDelPolygonId
        ];
        $this->sendRequest($request);
    }

    //=========== Ф-ии метода Change_position() ==========================================  
    protected function sendRequest($ajaxRequest) {

        header('Content-Type: text/json');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        //exit( json_encode( $ajaxRequest ) );
        echo( json_encode($ajaxRequest) );
        exit;
    }

    //Ф-я проверки залогинености пользователя. Возвращает true/false.
    //Если результат true записывает idGame, idGamer.
    protected function loggout() {
      
        if (isset($this->session['logg']) && $this->session['logg'] === TRUE) {
            $this->idGame = ( isset($this->session['idGame']) ) ? (int) $this->session['idGame'] : 0;
            $this->idGamer = ( isset($this->session['idGamer']) ) ? (int) $this->session['idGamer'] : 0;
            $this->idEnemy = ( isset($this->session['idEnemy']) ) ? (int) $this->session['idEnemy'] : 0;
            $this->startTime = ( isset($this->session['startTime']) ) ? $this->session['startTime'] : 0;
            if (!preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $this->startTime)) {
                return FALSE;
            }
            if (!$this->existenceUser($this->idGamer)) {
                return FALSE;
            }
            if (!$this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy)) {
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    // Ф-я проверки существования в таблице `user` юзера с заданным id. Возвращает true/false.
    protected function existenceUser($idUser) {
        $query = 'SELECT count(*)  FROM `user` WHERE `id` = ' . $idUser;
        $col = Yii::$app->db->createCommand($query)->queryScalar();
        return ( $col ) ? TRUE : FALSE;
    }

    // Ф-я проверки существования игры. Возвращает true/false.
    // Принимает $idGame, $idGamer, $idEnemy
    protected function existenceGame($idGame, $idGamer, $idEnemy) {
        $query = ' SELECT count(*) FROM `game` WHERE `id`= :idGame  and '
                . ' ( (  `user1_id`= :idGamer AND `user2_id` = :idEnemy ) OR '
                . ' ( `user1_id`= :idEnemy AND `user2_id` = :idGamer ) ) ';
        $col = Yii::$app->db->createCommand($query)
                ->bindValues([':idGame' => $idGame, ':idGamer' => $idGamer, ':idEnemy' => $idEnemy])
                ->queryScalar();
        return ( $col ) ? TRUE : FALSE;
    }

    // Ф-я получения состояния игры(игра продолжается или закончена). 
    // Возвращает [ 'statusGame' => true/false,'winner' => 'me'/'opponent'/'draw'/null,
    //                           'scoresMe' => scores, 'scoresEnemy' => scores ]
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

    // Ф-я добавления новой точки в БД. Возвращает id новой точки.
    protected function addDot($position) {

        $query = ' INSERT INTO `user_has_points`  SET `user_id` = ' . $this->idGamer;
        $query .= ' ,  `accuracy` = ' . $position->accuracy . ' , `game_id` = ' . $this->idGame;
        $query .= ', `point` = PointFromText( "POINT( ' . $position->latitude . ' ' . $position->longitude . ' )"  ) ';
        $query .= ', `status` = 1 ';
        Yii::$app->db->createCommand($query)->execute();
        $idNewDot = Yii::$app->db->createCommand(' SELECT LAST_INSERT_ID() ')->queryOne();

        $this->scores++; // Добавление очков
        return $idNewDot;
    }

    // Ф-я добавления нового полигона в БД. Возвращает id нового полигона.
    // Принимает массив результатов работы ф-ии getPossiblePoligon().
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

    // Ф-я проверки на попадание новой точки в полигон. Возвращает true/false.
    protected function inPolygons($position) {
        $query = ' SELECT count(*) as col FROM `user_has_polygons` '
                . 'WHERE   `status` = 1 AND `game_id` = ' . $this->idGame 
                . ' AND ST_Within( PointFromText( "POINT( ' . $position->latitude . " " . $position->longitude
                . ' )" ) , `polygon` ) = 1  ';
        $col = Yii::$app->db->createCommand($query)->queryOne();
        return ( $col['col'] ) ? TRUE : FALSE;
    }

    // Ф-я удаления  точек. Сохраняет id удаленных точек в БД. Принимает idGamer - id игрока
    // необязательный id точки и параметр $route. Если $routre = true, удаляются точки
    // id которых >=  id переданной. Если $routre = false, удаляются точки с id <  id переданной. 
    // $equality - условие отрезания хвоста. false - отрезаем id < $idDot, true - id <= $idDot
    protected function cutTail($idGamer, $idDot = 0, $equality = false, $route = false) {
        $query = User_has_points::find()->select('id');
        if ($idDot <= 0) {
            $query->where(' `game_id` = :idGame  and `user_id` = :idGamer  AND  `status`=1  ')
                    ->addParams([':idGame' => $this->idGame, ':idGamer' => $idGamer]);
        } else {
            if ($route) {
                $query->where(' `id` >= :id and `game_id` = :idGame and `user_id` = :idGamer AND `status`=1 ');
            } else {
                if ($equality) {
                    $query->where(' `id` <= :id and `game_id` = :idGame and `user_id` = :idGamer AND `status`=1 ');
                } else {
                    $query->where(' `id` < :id and `game_id` = :idGame and `user_id` = :idGamer AND `status`=1 ');
                }
            }
            $query->addParams([':id' => $idDot, ':idGame' => $this->idGame, ':idGamer' => $idGamer]);
        }
        foreach ($query->all() as $value) {
            $deleteDot = new \app\models\Deleted_points;
            $deleteDot->game_id = $this->idGame;
            $deleteDot->point_id = $value['id'];
            $deleteDot->save();
            $value->status = 0;
            $value->update();
        }
        return;
    }

    // Ф-я проверки на повторное посещение точки. Возвращает наименьший из id точек координаты
    // которых совпадают с координатами новой позиции. Если нет совпадений координат ( эта позиция
    // новая ) - возвращает false.
    // !!!!!! - пререписать для учета радиуса точности - !!!!!!!
    // 1м радиуса точности соотв 0,0000075 градусной меры
    protected function repeatVisit($position) {

        //  $radiusAccuracy = 0.00001;  // !!!!! - написать рассчет радиуса точности
        $dist = ( $position->accuracy > 20 ) ? $position->accuracy : 20;
        if ( $dist > 40 ){ $dist = 40; }
        $radiusAccuracy = 0.0000075 * $dist;
        //$radiusAccuracy = 0.000375;
        $strQuery = " SELECT `id` FROM `user_has_points` WHERE `game_id`= " . $this->idGame
                . " AND `user_id`=" . $this->idGamer . " AND  `status`='1'  AND  "
                . " ST_Distance( `point`, PointFromText('POINT(" . $position->latitude . " " . $position->longitude . ")')) "
                . " < " . $radiusAccuracy . " ORDER BY 'id' LIMIT 1 ";


        $query = Yii::$app->db->createCommand($strQuery)->queryOne();

        return ( $query === FALSE) ? FALSE : $query['id'];
    }

    // Ф-я получения массива точек. Принимает id начальной точки.
    // Возвращает массив ассоциативных массивов данных запроса  точек у которых id >= переданному.
    protected function getPossiblePoligon($idDot = 0) {
        $strQuery = "SELECT `id`, `user_id`, X( `point` ) as x, Y( `point` ) as y, `accuracy`, `timestamp`, `game_id` "
                . "  FROM `user_has_points` WHERE "
                . " `id` >= " . $idDot . " AND `status` = '1' AND `game_id` = " . $this->idGame
                . " AND `user_id` = " . $this->idGamer;
        $query = Yii::$app->db->createCommand($strQuery)->queryAll();
        return $query;
    }

    // Ф-я поиска точек попавших в полигон. Прнимает id полигона и igGamer, чьи точки ищем
    // Возвращает наибольший из id всех точек, попавших в полигон
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

    // Ф-я поиска полигонов попавших во вновь созданный полигон. 
    // Принимает id противника и нового id полигона. Возвращает массив id полигонов или false.
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

    // Ф-я сохранения заработанных очков в БД
    protected function addScores($idGame, $idGamer, $scores) {
        $query = \app\models\Game::findOne($idGame);
        $columnName = ( (int) $query->user1_id == (int) $idGamer ) ? 'user1_scores' : 'user2_scores';
        $query->$columnName += $scores;
        $query->update();

        return;
    }

    // Ф-я проверки таймаута.Принимает строку со стартовым временем. Возвращает true/false.
    protected function isTimeOut($startTimeStr) {

        $dt = \DateTime::createFromFormat("Y-m-d H:i:s", $startTimeStr);
        $dt2 = new \DateTime();
        if (!is_object($dt2) || !is_object($dt)) {
            return FALSE;
        }
        $time = $dt2->getTimestamp() - $dt->getTimestamp();
        // return $time;
        return ( $time > 113900 ) ? TRUE : FALSE;
        // return TRUE;
        return FALSE;
    }

    // Ф-я завершения игры.
    protected function gameOver() {
        Yii::$app->runAction('ruling/stop-game');
        return;
    }

    //=========== Ф-ии метода Get_change() =====ruling/get-ready=========================================
    // Ф-я получения массива новых точек. Принимает id последней отображенной точки .
    // Возвращает массив [ { 'gamer' : 'me/opponent' ,'id' : id, 'latitude' : latitude , 'longitude' : longitude }, ... ] 
    //  для передачи браузеру на отрисовку 
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

    // Выбор полигонов для передачи на отрисовку. Принимает id последнего отображенного полигона.
    // Возвращает массив для передачи браузеру на отрисовку 
    //   [ {  'gamer' : 'me/opponent', 'id' : id, 'arrDot' : [  { 'latitude' : latitude , 'longitude' : longitude }, ... ] }, ... ]
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

    // Ф-я получения массива точек [  { 'latitude' : latitude , 'longitude' : longitude }, ... ]
    // из результата выборки колонки `polygon` ф-ей MYSQL AsText().
    // возвращает массив с коорд.точек
    protected function getDotsOfPolygon($srtPolygon) {
        $arrDots = [];
        $arrMatches = [];
        $col = preg_match_all('/([0-9]{1,3}\.[0-9]+) ([0-9]{1,3}\.[0-9]+)/', $srtPolygon, $arrMatches);
        for ($i = 0; $i < $col; $i++) {
            $arrDots[] = [$arrMatches[1][$i], $arrMatches[2][$i]];
        }
        return $arrDots;
    }

    // Ф-я получения массива удаленных точек. Принимает id последней записи точек для удаления.
    // Возвращает массив   [ lastId,  [ { 'id' : id }, ... ]  ] для передачи браузеру на отрисовку 
    protected function getDotsForDelete($prevLastId = 0) {
        $deleteDots = [];
        $newLastId = $prevLastId;
        $query = Deleted_points::find()
                ->select(' id, point_id')->where('id > :id and game_id = :idGame')
                ->addParams([':id' => $prevLastId, ':idGame' => $this->idGame])
                ->asArray()
                ->all();
        foreach ($query as $value) {
            $deleteDots[] = ['id' => $value['point_id']];
            $newLastId = $value['id'];
        }
        return [$newLastId, $deleteDots];
    }

    // Ф-я получения массива удаленных полигонов. Принимает id последней записи полигонов для удаления.
    // Возвращает массив   [ lastId,  [ { 'id' : id }, ... ]  ] для передачи браузеру на отрисовку  
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

}
