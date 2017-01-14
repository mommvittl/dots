<?php

namespace app\controllers;
use Yii;
use app\models\User_has_points;
use app\models\User_has_polygons;
use app\models\deleted_points;
use app\models\Deleted_polygons;

ini_set('session.use_only_cookies',true);
session_start();
 $_SESSION['logg'] = TRUE;
 $_SESSION['idGame'] = 1;
 $_SESSION['$idEnemy'] = 2;
 $_SESSION['$startTime'] = 1;

class RoundController extends \yii\base\Controller{
   
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
 
  public function actionIndex() {
     
        $idGame = 1;
        $lat = 49.9415902;
        $lon = 36.3085217;
         $radiusAccuracy = 0.00001;
         $prevLastId = 33;
         
         $qq = " SELECT `id` FROM `user_has_points` WHERE `game_id`='1' AND `status`='1' AND
	ST_Distance( `point`, PointFromText('POINT(" . $lat . " " . $lon .  ")')) < 0.0001 ORDER BY 'id' LIMIT 1 ";       
         $query = Yii::$app->db->createCommand( $qq  )->queryOne();
      /*
           $query = User_has_points::find()->select( 'id' )
              ->where( ' `game_id`= "1" AND `status`= "1" AND
	ST_Distance( `point`, PointFromText("POINT( 49.9415902 36.3085217 )")) < 0.00001 ' )
                ->orderBy('id ASC')->limit(1)->one();  
        */
      
       return  $this->render('test' , [ 'dots' =>$query ]);
  }
    
  public function actionChange_position() {
        
       // Проверка залогинен ли юзер
         if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message' ] );
        }   
        
        // Создание нового обьекта
        $strParameter = filter_input(INPUT_POST, 'data');
        $newPosition = json_decode($strParameter);
        
        // Временная функция для отладки. Заполнение  переменной 'idGame'
        $this->idGamer =  $newPosition->idGamer ;  
        // Проверка валидности новых данных
        if( !$this->newPositionValidate($newPosition) ){
             $this->sendRequest( [  'status' => 'error', 'message' => 'error message validate' ] );
        }
    
        //Проверка новой точки на попадание в полигон    
        if( $this->inPolygons( $newPosition ) ){
            //Обрезаем хвост
            $this->cutTail(  );
             $this->sendRequest( [ 'status' => 'ok' ] ); 
        }
        
        // Проверка на повторное посещение точки
        $repeat = $this->repeatVisit( $newPosition );
        if ( $repeat === false ){
            $idNewDot =  $this->addDot(  $newPosition );     
             $this->sendRequest( [ 'status' => 'ok' ] );
        }

           
           $this->sendRequest( [  'status' => 'end', 'message' => 'end function Change_position' ] );
  }
    
  public function actionGet_change() {
        
        // Проверка залогинен ли юзер
        if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message 1' ] );
        }   
       
        // Создание нового обьекта с параметрами запроса
        $strParameter = filter_input(INPUT_POST, 'data');
        $parameterQuery = json_decode($strParameter);
        
        // Временная функция для отладки. Заполнение  переменной 'idGame'
        $this->idGamer =  $parameterQuery->idGamer ;  
        
        // Выбор данных для передачи на отрисовку  
        $this->arrAddDots = $this->getDotsForAdd( $parameterQuery->lastDotId );
         $this->arrAddPolygon = $this->getPolygonForAdd( $parameterQuery->lastPolygonId );
        list(  $this->lastDelDotId, $this->arrIdDeleteDots ) =  $this->getDotsForDelete( $parameterQuery->lastDelDotId );
        list(   $this->lastDelPolygonId, $this->arrIdDeletePolygon )  = $this->getPolygonForDelete( $parameterQuery->lastDelPolygonId );
        
        // формирование ответа для браузера
        $request = [
                          'arrAddDots'  => $this->arrAddDots,  
                          'arrAddPolygon' => $this->arrAddPolygon,  
                          'arrIdDeleteDots' => $this->arrIdDeleteDots, 
                          'arrIdDeletePolygon'  => $this->arrIdDeletePolygon, 
                          'lastDelDotId'  => $this->lastDelDotId, // lastDelDotId,
                          'lastDelPolygonId'  => $this->lastDelPolygonId // lastDelPolygonId
                           ];
        $this->sendRequest($request);
  }
  //=========== Ф-ии метода Change_position() ==========================================  
  protected function sendRequest($ajaxRequest) {
        
         header('Content-Type: text/XML');
         header('Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); 
         header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT'); 
         header('Cache-Control: no-cache, must-revalidate'); 
         header('Pragma: no-cache');
     
         exit( json_encode( $ajaxRequest ) );
              
  }
    
    //Ф-я проверки залогинености пользователя. Возвращает true/false.
    //Если результат true записывает idGame, idGamer.
  protected function loggout() {
        if ( isset($_SESSION['logg']) &&  $_SESSION['logg'] === TRUE ){
            $this->idGame = $_SESSION['idGame'];
            $this->idGamer = $_SESSION['idGamer'];
            $this->idGamer = $_SESSION['$idEnemy'];
            $this->idGamer = $_SESSION['$startTime'];
       
            return TRUE;
        }
        return  FALSE ;
  }
    
    //Ф-я валидации новой позиции. Возвращает true/false.
  protected function newPositionValidate($position) {
        
         if ( !is_object($position) ){  return FALSE; }
         if ( filter_var( $position->latitude , FILTER_VALIDATE_FLOAT)  === false ) {  return FALSE; }  ;
         if ( filter_var( $position->longitude , FILTER_VALIDATE_FLOAT)  === false ) {  return FALSE; }  ;
         if ( filter_var( $position->accuracy , FILTER_VALIDATE_INT)  === false ) {  return FALSE; }  ;
         if ( filter_var( $position->speed , FILTER_VALIDATE_INT)  === false ) {  return FALSE; }  ;
  
         if ( ($position->latitude  <= 0) || ($position->latitude  >=180 ) ) {  return FALSE; }  ;
         if ( ( $position->longitude <= 0 ) || ( $position->longitude >= 90 ) ) {  return FALSE; }  ;
         if (  ($position->accuracy <= 0) || ($position->accuracy >=500 )  ) {  return FALSE; }  ;
         if (  $position->speed   <= 0 ) {  return FALSE; }  ;
         $query = ' SELECT ST_Distance( `point` , PointFromText( "POINT( ' .  $position->latitude;
         $query .=  ' ' .  $position->longitude . ' )"  )  )  AS dist FROM `user` where 1 ' ;
         $distanse =   Yii::$app->db->createCommand( $query )->queryOne() ;
         if ( $distanse[ 'dist' ] > 1 ) {  return FALSE; }  
         return TRUE; 
      
  } 
    
    // Ф-я добавления новой точки в БД. Возвращает id новой точки.
  protected function addDot($position) {
       
         $query = ' INSERT INTO `user_has_points`  SET user_id = ' . $this->idGamer  ;
         $query .= ' ,  accuracy = ' .  $position->accuracy . ' , game_id = ' .  $this->idGame;
         $query .= ', point = PointFromText( "POINT( ' .  $position->latitude . ' ' .  $position->longitude . ' )"  ) ' ; 
         $query .= ', status = 1 ';
         Yii::$app->db->createCommand( $query )->execute();
         $idNewDot = Yii::$app->db->createCommand('SELECT LAST_INSERT_ID()') ->queryOne();
                
        return  $idNewDot ;
  }
    
    // Ф-я проверки на попадание новой точки в полигон. Возвращает true/false.
  protected function inPolygons($position) {
          $query = User_has_polygons::find()
                  ->where(  'ST_Within( PointFromText( "POINT( :lat :lon )" ) , `polygon` ) = 1'  )
                  ->addParams( [ ':lat' => $position->latitude, ':lon' => $position->longitude ] )->count();
          return ( $query ) ? TRUE : FALSE ;
  }
    
    // Ф-я удаления хвоста переданной точки. Сохраняет id удаленных точек в БД.
    // Принимает id точки после которой надо отрезать хвост.
  protected function cutTail( $idDot = 0 ) {
         $query =    User_has_points::find()->select('id');
         if ( $idDot > 0 ){
              $query->where(  'id < :id and game_id = :idGame'  )
                      ->addParams( [':id' => $idDot, ':idGame' => $this->idGame ] );
         }else{
             $query->where(  'game_id = :idGame'  )->addParams( [ ':idGame' => $this->idGame ] );
         }       
         foreach(  $query->all() as $value){          
             $deleteDot = new \app\models\Deleted_points;   
             $deleteDot->game_id = $this->idGamer;
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
  protected function repeatVisit($position) {   
     $radiusAccuracy = 0.00001;  // !!!!! - написать рассчет радиуса точности
     $strQuery = " SELECT `id` FROM `user_has_points` WHERE `game_id`= " . $this->idGame
        . " AND  `status`='1'  AND  "
        . " ST_Distance( `point`, PointFromText('POINT(" . $position->latitude . " " . $position->longitude .  ")')) "
             . " < "  . $radiusAccuracy   . " ORDER BY 'id' LIMIT 1 ";     
         $query = Yii::$app->db->createCommand( $strQuery  )->queryOne();
        return ( $query === FALSE) ? FALSE : $query[ 'id' ] ;
       /* 
      $query = User_has_points::find()->select( 'id' )
              ->where( ' `game_id`= :idGame AND `status`= "1" AND
	ST_Distance( `point`, PointFromText("POINT( :lat :lon )")) < :radiusAccuracy ' )
               ->addParams(  [ ':idGame' => $this->idGame, ':lat' => $position->latitude,
                       ':lon' => $position->longitude, ':radiusAccuracy' => $radiusAccuracy ] )
                ->orderBy('id ASC')->limit(1)->one();  

           $query = User_has_points::find()->select( 'id' )
              ->where( ' `game_id`= "1" AND `status`= "1" AND
	ST_Distance( `point`, PointFromText("POINT( 49.9415902 36.3085217 )")) < 0.00001 ' )
                ->orderBy('id ASC')->limit(1)->one();  
        */ 
  }
 //=========== Ф-ии метода Get_change() ==============================================
 // Ф-я получения массива новых точек. Принимает id последней отображенной точки .
  // Возвращает массив [ { 'gamer' : 'me/opponent' ,'id' : id, 'latitude' : latitude , 'longitude' : longitude }, ... ] 
  //  для передачи браузеру на отрисовку 
  protected function getDotsForAdd( $lastDot = 0 ) {  
    $dots = [];
    $query = User_has_points::find()->select(" `id`, `user_id`, X( `point` ), Y( `point` ), `accuracy`, `timestamp` ")
            ->where(  'id > :id and game_id = :idGame  and `status` = 1 ' )
             ->addParams(  [':id' => $lastDot, ':idGame' => $this->idGame ]  )->asArray()->all();
    foreach ($query as $value) {
        $gamer = ( $value['user_id'] == $this->idGamer ) ? 'me' : 'opponent' ;
        $dots[] = [
                        'gamer' => $gamer,
                        'id' => $value[ 'id' ],
                        'latitude' => $value[ 'X( `point` )' ] ,
                        'longitude' => $value[ 'Y( `point` )' ]
                        ];    
    }
    return  $dots;
  }
   
  // Выбор данных для передачи на отрисовку  
  protected function getPolygonForAdd( $idPolygon = 0 ) {  // [ {  'gamer' : 'me/opponent', 'id' : id, 'arrDot' : [  { 'latitude' : latitude , 'longitude' : longitude }, ... ] }, ... ],

     return;
  }
  
  // Ф-я получения массива удаленных точек. Принимает id последней записи точек для удаления.
  // Возвращает массив   [ lastId,  [ { 'id' : id }, ... ]  ] для передачи браузеру на отрисовку 
  protected function getDotsForDelete( $prevLastId = 0 ) {   
     $deleteDots = [];
     $newLastId = $prevLastId;
     $query = Deleted_points::find()->select( ' id, point_id' )->where( 'id > :id and game_id = :idGame' )
             ->addParams( [':id' => $prevLastId, ':idGame' => $this->idGame ] )->asArray()->all();
     foreach( $query as $value ){
         $deleteDots[]  = [ 'id' => $value['point_id'] ];
         $newLastId = $value['id'];
     }
     return  [ $newLastId,  $deleteDots  ]  ;
  }
  
  // Ф-я получения массива удаленных полигонов. Принимает id последней записи полигонов для удаления.
  // Возвращает массив   [ lastId,  [ { 'id' : id }, ... ]  ] для передачи браузеру на отрисовку  
  protected function getPolygonForDelete( $prevLastId = 0 ) {  
   
     $deletePolygon = [];
     $newLastId = $prevLastId;
     $query = Deleted_polygons::find()->select( ' id, polygon_id ' )->where(  'id > :id and game_id = :idGame' )
             ->addParams(  [':id' => $prevLastId, ':idGame' => $this->idGame ]  )->asArray()->all();
     foreach( $query as $value ){
         $deletePolygon[]  = [ 'id' => $value['polygon_id'] ];
         $newLastId = $value['id'];
     }
     return [ $newLastId,  $deletePolygon  ] ;
  }
  
}
