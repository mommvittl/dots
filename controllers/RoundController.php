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
  protected $arrAddPoligon = [];
  protected $arrIdDeleteDots = [];
  protected $arrIdDeletePoligon = [];
  protected $lastDelDotId = 0;
  protected $lastDelPoligonId = 0;
 
  public function actionIndex() {
     
        $idGame = 1;
        $lat = 49.9412902;
        $lon = 36.3085217;
 
         $prevLastId = 33;
       $query = User_has_points::find()->select( " `id`, `user_id`, X( `point` ), Y( `point` ), `accuracy`, `timestamp` " )->where(  'id > :id and game_id = :idGame' )
             ->addParams(  [':id' => $prevLastId, ':idGame' => $idGame ]  )->asArray()->all();
      //  $query = Deleted_polygons::find()->select( ' id, poligon_id ' )->where(  'id > :id and game_id = :idGame' )->asArray()->all();
            //  ->addParams(  [ ':id' => $prevLastId, ':idGame' => $this->idGame ]  )
        
         
      
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
          
        // Проверка валидности новых данных
        if( !$this->newPositionValidate($newPosition) ){
             $this->sendRequest( [  'status' => 'error', 'message' => 'error message' ] );
        }
    
        //Проверка на попадание в полигон
        if( $this->inPolygons( $newPosition ) ){
            //Обрезаем хвост
            $this->cutTail( 33 );
             $this->sendRequest( [ 'status' => 'ok' ] ); 
        }

        $idNewDot =  $this->addDot(  $newPosition );    
        $this->sendRequest($idNewDot);      
        
  }
    
  public function actionGet_change() {
        
        // Проверка залогинен ли юзер
        if( !$this->loggout() ){
            $this->sendRequest( [  'status' => 'error', 'message' => 'error message 1' ] );
        }   
        
        // Создание нового обьекта с параметрами запроса
        $strParameter = filter_input(INPUT_POST, 'data');
        $parameterQuery = json_decode($strParameter);
        
        // Выбор данных для передачи на отрисовку  
        $this->arrAddDots = $this->getDotsForAdd( $parameterQuery->lastDotId );
         $this->arrAddPoligon = $this->getPoligonForAdd( $parameterQuery->lastPoligonId );
        list(  $this->lastDelDotId, $this->arrIdDeleteDots ) =  $this->getDotsForDelete( $parameterQuery->lastDelDotId );
        list(   $this->lastDelPoligonId, $this->arrIdDeletePoligon )  = $this->getPoligonForDelete( $parameterQuery->lastDelPoligonId );
        
        // формирование ответа для браузера
        $request = [
                          'arrAddDots'  => $this->arrAddDots,  
                          'arrAddPoligon' => $this->arrAddPoligon,  
                          'arrIdDeleteDots' => $this->arrIdDeleteDots, 
                          'arrIdDeletePoligon'  => $this->arrIdDeletePoligon, 
                          'lastDelDotId'  => $this->lastDelDotId, // lastDelDotId,
                          'lastDelPoligonId'  => $this->lastDelPoligonId // lastDelPoligonId
                           ];
        $this->sendRequest($request);
  }
    
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
    
     return TRUE; 
      
  }
    
    // Ф-я добавления новой точки в БД. Возвращает id новой точки.
  protected function addDot($position) {
        
         $query = ' INSERT INTO `user_has_points`  SET user_id = ' . $this->idGamer  ;
         $query .= ' ,  accuracy = ' .  $position->accuracy . ' , game_id = ' .  $this->idGame;
         $query .= ', point = PointFromText( "POINT( ' .  $position->latitude . ' ' .  $position->longitude . ' )"  ) ' ;       
         Yii::$app->db->createCommand( $query )->execute();
         $idNewDot = Yii::$app->db->createCommand('SELECT LAST_INSERT_ID()') ->queryOne();
                
        return  $idNewDot ;
  }
    
    // Ф-я проверки на попадание новой точки в полигон. Возвращает true/false.
  protected function inPolygons($position) {
        // return TRUE;
        return FALSE;
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
             $value->delete();
         }        
       return;
  }
         
  protected function getDotsForAdd( $lastDot = 0 ) {  // [ { 'gamer' : 'me/opponent' ,'id' : id, 'latitude' : latitude , 'longitude' : longitude }, ... ],
    $dots = [];
    $query = User_has_points::find()->select(" `id`, `user_id`, X( `point` ), Y( `point` ), `accuracy`, `timestamp` ")
            ->where(  'id > :id and game_id = :idGame' )
             ->addParams(  [':id' => $lastDot, ':idGame' => $this->idGame ]  )->asArray()->all();
    foreach ($query as $value) {
        $gamer = ( $value['user_id'] == $this->idGamer ) ? 'me' : 'opponent' ;
        $dots[] = [ 'gamer' => $gamer, 'id' => $value[ 'id' ], 'latitude' => $value[ 'X( `point` )' ] ,  'longitude' => $value[ 'Y( `point` )' ]   ];    
    }
    return  $dots;
  }
    
  protected function getPoligonForAdd( $idPolygon = 0 ) {  // [ {  'gamer' : 'me/opponent', 'id' : id, 'arrDot' : [  { 'latitude' : latitude , 'longitude' : longitude }, ... ] }, ... ],

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
  protected function getPoligonForDelete( $prevLastId = 0 ) {  
   
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
