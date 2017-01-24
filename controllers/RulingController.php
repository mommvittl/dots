<?php

namespace app\controllers;
use app\models\User;
use Yii;


ini_set('session.use_only_cookies',true);
session_start();

class RulingController extends \yii\base\Controller{
  
     protected $idGame = null;
     protected $idGamer = null;
     protected $idEnemy = null;
     
     public function actionIndex() {
       
         $this->idGamer = 33;
         $idGame = 10;
         $idGamer =1;
         $idEnemy =2;  
         $query = [
                        'idGame' => $_SESSION['idGame'] , 
                         'idGamer' =>  $_SESSION['idGamer'] , 
                         'idEnemy' =>  $_SESSION['idEnemy'] , 
                          'startTime' => $_SESSION['startTime'] ,
                  'res' => $res
                         ];
       
         return  $this->render('test' , [  'dots' =>$query  ]);
          
          
     }
     
    // Ф-я обработки запроса ready.  ------------------------------------------------------------------------------------------
    // Входные данные,json: { 'latitude' : latitude , 'longitude' : longitude, 'accuracy' : accuracy , 'speed' : speed }
    // Создает ( или обновляет ) запись в таблице ready с передаными координатами . Возвращает json
    //  { 'opponent' : 0 , 'idGame' : 0 ,
    //   'arrOpponents' : [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ] } 
    //   arrOpponents - массив потенциальных соперников, у которых есть статус ready .
    // Если в поле opponent_id записан id соперника - он возвращается в соот.поле ответа вместе с 
    // idGame. В сессии сохраняются $idGame, $idEnemy, $startTime - id игрока , его противника и 
    // стартовое время игры. Игра стартовала. Можно отправлять точки на обработку и получать данные
    public function actionGetReady() {
      
        // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error: access denied' ] );
        }   
          
         // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        
        // Валидация переданых координат
         if( !$this->newPositionValidate($newPosition) ){
             $this->sendRequest( [  'status' => 'error', 'message' => 'error: incorrect input data' ] );
        }
        
        // Создание новой записи в таблице `ready` или обновление координат, если запись есть
        // Принимает idGamer, возвращает данные поля opponent_id и idGame
        $request = $this->updateReady(  $newPosition );
        
        // Выборка всех игроков со статусом ready.        
        $request[ 'arrOpponents' ] =  $this->getOpponents();
        
         $this->sendRequest( $request );
       
    }
    
    // Ф-я отмены статуса ready -----------------------------------------------------------------------------------------------
    // Удаляет запись из таблицы ready пользователя с id, полученным из сессии, если у него еще
    // нет оппонента( т.е. поле `opponent_id` -  is null )
    public function actionStopReady() {
        
         // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error: access denied' ] );
        }  
        
        // Удаление записи из таблицы `ready`
        $query = ' DELETE FROM `ready` WHERE `user_id` = :idGamer AND  `opponent_id` is null ';
        $col = Yii::$app->db->createCommand( $query )
                ->bindValues( [ ':idGamer' => $this->idGamer ] )
                ->execute();
         $status = ( $col ) ? 'Ok' : 'error';  
         $this->sendRequest( [ 'status' => $status ] );
            
    }
    
    // Ф-я выбора соперника для игры --------------------------------------------------------------------------------------
    // Получает id выбранного соперника. Если ни игрок ни его выбраный противник не имеют открытых
    //  игр , то в таблице ready им заполняется поле opponent_id, в таблице game создается новая игра
    //  Возвращает статус ok/error + message error.
    public function actionEnemySelection() {
        
         // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => ' error: access denied ' ] );
        }
        
         // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        $this->idEnemy = (int)$newPosition->idEnemy;
     
        // Проверка существования противника с таким id
        if( !$this->existenceUser( $this->idEnemy  ) ){  $this->sendRequest( [ 'status' => 'error: idEnemy  does not exist ' ] );  }
          
        // проверка на отсутствие открытых игр (своих и потенциального противника)
        if( $this->inGame( $this->idGamer ) ||  $this->inGame(  $this->idEnemy ) ){
                 $this->sendRequest( [ 'status' => 'error: have unfinished games ' ] );           
        }
       
        // Проверка на готовность противника с переданным id
        if( !$this->isReady( $this->idEnemy ) || !$this->isReady( $this->idGamer ) ){  
            $this->sendRequest( [ 'status' => 'error: idEnemy or  idGamer its not ready ' ] );             
        }
        
        // Проверка не играем ли сам с собой
        if( $this->idEnemy ==  $this->idGamer ){ $this->sendRequest( [ 'status' => 'error: idEnemy ==  idGamer' ] ); }
         
        // Добавление  id противников друг другу в таблицу ready
        $this->addIdEnemy( $this->idEnemy ,  $this->idGamer  );
        
        // Создание записи в таблице game  
        $this->addGame( $this->idEnemy );      
           
        $this->sendRequest( [ 'status' => 'ok' ] );
    }
   
    // Ф-я завершения игры. Удаляет из таблицы ready строки игроков. Определяет победителя 
    // и корректирует таблицу game
    public function actionStopGame() {
       
         // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => ' error: access denied ' ] );
        }
        
        // Получение данных из сессии
        if( isset($_SESSION[ 'idGame' ] ) ){ $this->idGame = (int)$_SESSION[ 'idGame' ]; }
        if( isset($_SESSION[ 'idEnemy' ] ) ){ $this->idEnemy = (int)$_SESSION[ 'idEnemy' ]; }
            
        // Проверка существования игры.
       if( !$this->existenceGame($this->idGame, $this->idGamer, $this->idEnemy) ) {
            $this->sendRequest( [  'status' => 'error', 'message' => ' error: access denied ' ] );
       }
 
       // Удаление записей из таблицы ready
       $this->deleteReady(  $this->idGamer, $this->idEnemy );
       
       // определение победителя и обновление таблицы game
       $this->getWinner( $this->idGame );
       
        $this->sendRequest( [ 'status' => 'ok' ] ); 
    }
    // Внутренние ф-ии ======================================================
    protected function sendRequest($ajaxRequest) {
        
         header('Content-Type: text/json');
         header('Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); 
         header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT'); 
         header('Cache-Control: no-cache, must-revalidate'); 
         header('Pragma: no-cache');
    
        //exit( json_encode( $ajaxRequest ) );
         echo( json_encode( $ajaxRequest ) ); 
         exit;
    }
    
    //Ф-я проверки залогинености пользователя. Возвращает true/false.
    //Если результат true записывает idGame, idGamer.
  protected function loggout() {
        if ( isset($_SESSION['logg']) &&  $_SESSION['logg'] === TRUE ){
            $this->idGamer = (int)$_SESSION['idGamer'];   
            return $this->existenceUser( $this->idGamer );
        }
        return  FALSE ;
  }
  
   // Ф-я проверки существования игры. Возвращает true/false.
  // Принимает $idGame, $idGamer, $idEnemy
  protected function existenceGame( $idGame, $idGamer, $idEnemy ) {
      $query = ' SELECT count(*) FROM `game` WHERE `id`= :idGame and `winner_id` is NULL and ' 
         . ' ( (  `user1_id`= :idGamer AND `user2_id` = :idEnemy ) OR '
         . ' ( `user1_id`= :idEnemy AND `user2_id` = :idGamer ) ) ';
       $col = Yii::$app->db->createCommand( $query )
              ->bindValues( [ ':idGame' => $idGame , ':idGamer' => $idGamer , ':idEnemy' => $idEnemy ] )
              ->queryScalar();
        return ( $col  ) ? TRUE : FALSE ;
  }
  
  // Ф-я проверки существования в таблице `user` юзера с заданным id. Возвращает true/false.
  protected function existenceUser( $idUser ) {
      $query = 'SELECT count(*)  FROM `user` WHERE `id` = :idGamer';            
      $col = Yii::$app->db->createCommand( $query  )
              ->bindValues( [ ':idGamer' => $idUser ] )
              ->queryScalar();  
      return ( $col  ) ? TRUE : FALSE ;
  }
  
  // Ф-я обновления/записи в таблицу ready статуса готов и координат юзера 
  // Принимает обьект с координатами. Возвращает данные поля `opponent_id` или 0,
  // если запись новая ( соперника пока нет ) и idGame, если `opponent_id` != 0
  protected function updateReady( $position ) {
      $idEnemy = 0;
      $idGame = 0;
      $row = \app\models\Ready::find()
              ->where(' `user_id` = :idGamer ')
              ->addParams( [ ':idGamer' => $this->idGamer  ] )
              ->one( );
      if( !$row && !is_object($row) ){
          $query = ' INSERT INTO `ready` SET `user_id` = :idGamer ';
          $query .= ' , `point` = PointFromText("POINT( ' .  $position->latitude . ' ' . $position->longitude . ' )") ';
          Yii::$app->db->createCommand( $query )
              ->bindValues( [  ':idGamer' => $this->idGamer ] )    
              ->execute();
      }else{
         // Обновление координат в БД
          $query = ' UPDATE `ready` SET '
               . ' `point` = PointFromText("POINT( ' .  $position->latitude . ' ' . $position->longitude . ' )") '
               . ' WHERE `user_id` = :idGamer '; 
          Yii::$app->db->createCommand( $query )
              ->bindValues( [  ':idGamer' => $this->idGamer ] )  
              ->execute();
          // Если есть оппонент - передает idGame
          if( $row->opponent_id ){
              $idEnemy =  (int)$row->opponent_id;
              $query = \app\models\Game::find()
                       ->select(' `id`, `start_time` ')
                       ->where( ' (`user1_id` = :idGamer OR `user2_id`= :idGamer ) AND `winner_id` is NULL' )
                       ->addParams( [ ':idGamer' => $this->idGamer ] )
                       ->one();
              $idGame = $query->id;
              $startTime = $query->start_time;
              // Загружаем в сессию данные о игре
              $_SESSION['idGame'] = $idGame;
              $_SESSION['idEnemy'] = $idEnemy;
              $_SESSION['startTime'] = $startTime;                    
          }
      }
      return  [ 'opponent' => $idEnemy, 'idGame' => $idGame  ];
  }
  
  // ф-я выбора всех игроков со статусом ready. Возвращает массив
  // [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ]
  protected function getOpponents() {
      $arrOpponents = [ ];
      $query = 'SELECT `user_id`, X(`point`) as x, Y(`point`) as y, u.`username`  FROM `ready`, '
              . ' `user` as u   WHERE `opponent_id` is null AND `user_id` = u.`id` '
              . 'AND `user_id` != :idGamer ' ;
      $row =  Yii::$app->db->createCommand( $query )
               ->bindValues( [ ':idGamer' => $this->idGamer ] )
              ->queryAll();    
      foreach( $row as $value ){
          $arrOpponents[ ] = [
                                        'id' => $value[ 'user_id' ] ,  
                                        'nick'  => $value[ 'username' ] ,
                                        'latitude'  => $value[ 'x' ] , 
                                        'longitude'  => $value[ 'y' ] 
                                        ];
      }
      return $arrOpponents;
  }
  
  // Ф-я записи данных новой игры в БД. Принимает idEnemy
  protected function addGame( $idEnemy ) {
      $query = new \app\models\Game();
      $query->user1_id = $this->idGamer;
      $query->user2_id = $idEnemy;
      $query->save();
  }
  
  // Ф-я проверки существования открытых игр для переданоого idGamer. Возвращает true/false.
  protected function inGame( $idGamer ) {
      $query = \app\models\Game::find()
              ->where( '  (`user1_id` = :idGamer OR `user2_id`= :idGamer ) AND `winner_id` IS  NULL ' )
              ->addParams( [ ':idGamer' => $idGamer ] )
              ->count();
      return ( $query ) ? TRUE : FALSE ;
  }
  
  // Ф-я проверки статуса ready у юзера с переданным id. Возвращает true/false.
  protected function isReady( $idGamer ) {
      $query = \app\models\Ready::find()
              ->where( '  `user_id` = :idGamer AND `opponent_id` IS  NULL ' )
               ->addParams( [ ':idGamer' => $idGamer ] )
              ->count();
      return ( $query ) ? TRUE : FALSE ;
  }
     
  // Ф-я добавление  id противников друг другу в таблицу ready
  protected function addIdEnemy( $idEnemy, $idGamer  ) {
       $query = " UPDATE `ready` SET `opponent_id` = :idEnemy WHERE `user_id` = :idGamer  ";
       Yii::$app->db->createCommand($query)
               ->bindValues( [  ':idGamer' => (int)$idGamer,  ':idEnemy' => (int)$idEnemy ] )
               ->execute() ;
       Yii::$app->db->createCommand($query)
               ->bindValues( [  ':idGamer' => (int)$idEnemy,  ':idEnemy' => (int)$idGamer ] )
               ->execute() ;
  }
  
  //Ф-я валидации новой позиции. Возвращает true/false.
  protected function newPositionValidate($position) {
        
         if ( !is_object($position) ){  return FALSE; }
         if ( filter_var( $position->latitude , FILTER_VALIDATE_FLOAT)  === false ) {  return FALSE; }  ;
         if ( filter_var( $position->longitude , FILTER_VALIDATE_FLOAT)  === false ) {  return FALSE; }  ;
        // if ( filter_var( $position->accuracy , FILTER_VALIDATE_INT)  === false ) {  return FALSE; }  ;
        // if ( filter_var( $position->speed , FILTER_VALIDATE_INT)  === false ) {  return FALSE; }  ;
  
         if ( ($position->latitude  <= 0) || ($position->latitude  >=180 ) ) {  return FALSE; }  ;
         if ( ( $position->longitude <= 0 ) || ( $position->longitude >= 90 ) ) {  return FALSE; }  ;
         //if (  ($position->accuracy <= 0) || ($position->accuracy >=500 )  ) {  return FALSE; }  ;
        // if (  $position->speed   <= 0 ) {  return FALSE; }  ;
         return TRUE; 
      
  } 
  
  // Ф-ии метода stopGame ==========================================================
  // Ф-я определения победителя. Записывает id победителя в таблицу game. Принимает idGame.
  protected function getWinner( $idGame ) {
      $query = \app\models\Game::findOne( (int)$idGame );
      $score1 = ( $query->user1_scores ) ?  (int)$query->user1_scores : 0 ;
      $score2 = ( $query->user2_scores ) ?  (int)$query->user2_scores : 0 ;
      if( $score1 != $score2 ){
          $winner = ( $score1 > $score2 ) ? $query->user1_id : $query->user2_id ;
      }else{ $winner = 0;  }  
      $query->winner_id  = $winner;
      $query->update();
      return;
  }
  
  // Ф-я удаления записей из таблицы ready для участников игры
  protected function deleteReady( $idGamer, $idEnemy ) {
      Yii::$app->db->createCommand()->delete('ready',[ 'user_id' => $idGamer, 'opponent_id' => $idEnemy  ])->execute();
      Yii::$app->db->createCommand()->delete('ready',[ 'user_id' => $idEnemy, 'opponent_id' => $idGamer  ])->execute();
      return;
  }
  
}
