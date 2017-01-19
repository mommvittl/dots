<?php

namespace app\controllers;
use app\models\User;
use Yii;


ini_set('session.use_only_cookies',true);
session_start();

class RulingController extends \yii\base\Controller{
  
     protected $idGamer = null;
     protected $idEnemy = null;
     
     public function actionIndex() {
         
         $this->idGamer = 33;
         $idGamer = 'sdsdd';
         $idEnemy = null;
         $query = [ $_SESSION['idGame'] , $_SESSION['idGamer'] , $_SESSION['$idEnemy'] , $_SESSION['$startTime'] ];
       
          return  $this->render('test' , [  'dots' =>$query ]);
          
          
     }
     
    // Ф-я обработки запроса ready ------------------------------------------------------------------------------------------
    public function actionGetReady() {
      
        // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message logg' ] );
        }   
          
         // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
          
        // Временная функция для отладки. Заполнение  переменной 'idGame', 'idEnemy' . !!!!!!!!!!!!!!!!!!!
        // $this->idGamer =  (int)$newPosition->idGamer ;  
        // конец временных данных. Удалить !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
  
        // Создание новой записи в таблице `ready` или обновление координат, если запись есть
        // Принимает idGamer, возвращает данные поля opponent_id и idGame
        $request = $this->updateReady(  $newPosition );
        
        // Выборка всех игроков со статусом ready.        
        $request[ 'arrOpponents' ] =  $this->getOpponents();
        
         $this->sendRequest( $request );
       
    }
    
    // Ф-я отмены статуса ready -----------------------------------------------------------------------------------------------
    public function actionStopReady() {
        
         // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message logg' ] );
        }  
        
        // Временная функция для отладки. Заполнение  переменной 'idGame', 'idEnemy' . !!!!!!!!!!!!!!!!!!!
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        //$this->idGamer =  (int)$newPosition->idGamer ;  
        // конец временных данных. Удалить !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        
        // Удаление записи из таблицы `ready`
        $query = ' DELETE FROM `ready` WHERE `user_id` = :idGamer AND  `opponent_id` is null ';
        $col = Yii::$app->db->createCommand( $query )
                ->bindValues( [ ':idGamer' => $this->idGamer ] )
                ->execute();
         $status = ( $col ) ? 'Ok' : 'error';  
         $this->sendRequest( [ 'status' => $status ] );
         //   
    }
    
    // Ф-я выбора соперника для игры --------------------------------------------------------------------------------------
    // Получает id выбранного соперника. Если ни игрок ни его выбраный противник не имеют открытых
    //  игр , то в таблице ready им заполняется поле opponent_id, в таблице game создается новая игра
    public function actionEnemySelection() {
        
         // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message logg' ] );
        }
        
         // Создание нового обьекта
        $strParameter = file_get_contents('php://input');
        $newPosition = json_decode($strParameter);
        $this->idEnemy = (int)$newPosition->idEnemy;
        
        // Временная функция для отладки. Заполнение  переменной 'idGame', 'idEnemy' . !!!!!!!!!!!!!!!!!!!
       // $this->idGamer =  (int)$newPosition->idGamer ;  
        // конец временных данных. Удалить !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      
        // Проверка существования противника с таким id
        if( !$this->existenceUser( $this->idEnemy  ) ){  $this->sendRequest( [ 'status' => 'error' ] );  }
          
        // проверка на отсутствие открытых игр (своих и потенциального противника)
        if( $this->inGame( $this->idGamer ) ||  $this->inGame(  $this->idEnemy ) ){
                 $this->sendRequest( [ 'status' => 'error' ] );           
        }
       
        // Проверка на готовность противника с переданным id
        if( !$this->isReady( $this->idEnemy ) || !$this->isReady( $this->idGamer ) ){  
            $this->sendRequest( [ 'status' => 'error' ] );             
        }
        
        // Проверка не играем ли сам с собой
        if( $this->idEnemy ==  $this->idGamer ){ $this->sendRequest( [ 'status' => 'error' ] ); }
         
        // Добавление  id противников друг другу в таблицу ready
        $this->addIdEnemy( $this->idEnemy ,  $this->idGamer  );
        
        // Создание записи в таблице game  
        $this->addGame( $this->idEnemy );      
      
       
        $this->sendRequest( [ 'status' => 'ok' ] );
           $this->sendRequest( [  'status' => 'test', 'gamer' =>  $this->idGamer, 'enemy' => $newPosition->idEnemy  ] ); 
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
              $_SESSION['$idEnemy'] = $idEnemy;
              $_SESSION['$startTime'] = $startTime;                    
          }
      }
      return  [ 'opponent' => $idEnemy, 'idGame' => $idGame  ];
  }
  
  // ф-я выбора всех игроков со статусом ready. Возвращает массив
  // [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ]
  protected function getOpponents() {
      $arrOpponents = [ ];
      $query = 'SELECT `user_id`, X(`point`) as x, Y(`point`) as y, u.`nick`  FROM `ready`, '
              . ' `user` as u   WHERE `opponent_id` is null AND `user_id` = u.`id` '
              . 'AND `user_id` != :idGamer ' ;
      $row =  Yii::$app->db->createCommand( $query )
               ->bindValues( [ ':idGamer' => $this->idGamer ] )
              ->queryAll();    
      foreach( $row as $value ){
          $arrOpponents[ ] = [
                                        'id' => $value[ 'user_id' ] ,  
                                        'nick'  => $value[ 'nick' ] , 
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
  
}
