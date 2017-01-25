<!DOCTYPE html>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-type" CONTENT="TEXT/HTML; charset=utf-8">
<TITLE>Список сотрудников</TITLE> 
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
</HEAD>
 <BODY>
 <h1>JS Test debugger</h1>
 <p><button type="button" id="startBut">Start</button><button type="button" id="stopBut">Stop</button></p>
 <div id="YMapsID" style="width: 900px; height: 700px; margin: 0 auto; "></div>

<script>
var ajaxGet = new AjaxGETResponse;
var ajaxPost = new AjaxGETResponse;

var myMap;
var myPlacemark;

var latitude = 49.9412902;
var longitude = 36.3085217;
var accuracy = 10;
var speed = 1;

var idGame = 1;
var idGamer = 1;
var idEnemy = 2;

var lastDotId = 0;
var lastPolygonId = 0;
var lastDelDotId = 0;
var lastDelPolygonId = 0;

var dots = { };
var polygons = { };
//var timerId = setInterval( getNewCommand, 1000 );
//------------------------------------------------------
var start = document.getElementById( 'startBut' );
start.onclick = getNewCommand;
//----------------------------------------------------
function getNewCommand(){
    var theParam = 'data=' + JSON.stringify( { 'lastDotId' : lastDotId, 'lastPolygonId' : lastPolygonId, 'lastDelDotId' : lastDelDotId, 'lastDelPolygonId' : lastDelPolygonId, 'idGamer' : idGamer } );
    ajaxGet.setAjaxQuery('http://dots/round/get_change' , theParam , getResponseScript , 'POST' , 'text');
}
function getResponseScript( responseXMLDocument ){
     alert(responseXMLDocument );
    var response = JSON.parse( responseXMLDocument );
    viewAddDots( response );
    viewAddPolygons( response );
    viewDeleteDots( response );
    viewDeletePolygons( response );
    lastDelDotId = response.lastDelDotId;
    lastDelPolygonId = response.lastDelPolygonId;
}
/*
{ 
	'arrAddDots' : [ { 'gamer' : 'me/opponent' ,'id' : id, 'latitude' : latitude , 'longitude' : longitude }, ... ],
	'arrAddPolygon' : [ {  'gamer' : 'me/opponent', 'id' : id, 'arrDot' : [  { 'latitude' : latitude , 'longitude' : longitude }, ... ] }, ... ],
	'arrIdDeleteDots' : [ { 'id' : id }, ... ],
	'arrIdDeletePolygon' : [ { 'id' : id }, ... ],
	'lastDelDotId' : lastDelDotId,
	'lastDelPolygonId' : lastDelPolygonId
 }
*/
//------------------------------------------------------
function  viewAddDots( responseData ){
    if( responseData.arrAddDots.length == 0 ){ return false; }
        for( var i = 0; i < responseData.arrAddDots.length; i++ ){
                var gamerColor = ( responseData.arrAddDots[ i ].gamer == 'me'  ) ? 'islands#darkOrangeCircleDotIcon' : 'islands#blueCircleDotIcon' ;
                lastDotId = responseData.arrAddDots[ i ].id ;
                dots[ lastDotId ] = new ymaps.Placemark( [ responseData.arrAddDots[ i ].latitude , responseData.arrAddDots[ i ].longitude ], { }, { preset: gamerColor  } );	
                myMap.geoObjects.add(  dots[ lastDotId ] );
        }
        return true;
}
function  viewAddPolygons( responseData ){
     if( responseData.arrAddPolygon.length == 0 ){ return false; }
     for( var i = 0; i < responseData.arrAddPolygon.length; i++ ){
         var newRing = [];
         var poligon = responseData.arrAddPolygon[ i ].arrDot;
         for( var j = 0; j < poligon.length; j++){
            newRing.push( [ poligon[ j ].latitude , poligon[ j ].longitude ] ); 
         }
         var gamerColor = ( responseData.arrAddPolygon[ i ].gamer == 'me'  ) ? '#FFA500' : '#87CEEB' ;
         lastPolygonId = responseData.arrAddPolygon[ i ].id ;
         polygons[ lastPolygonId ] = new ymaps.Polygon(
	[ newRing ],
	{ hintContent: "Многоугольник" },
	{ fillColor: gamerColor, strokeWidth: 8,opacity: 0.5 }											
	); 
         myMap.geoObjects.add( polygons[ lastPolygonId ] );
     }
}
function  viewDeleteDots( responseData ){
     if( responseData.arrIdDeleteDots.length == 0 ){ return false; }
     for( var i =0; i < responseData.arrIdDeleteDots.length; i++ ){
         var idDot = responseData.arrIdDeleteDots[ i ].id;
         myMap.geoObjects.remove( dots[ idDot ] );
         delete dots[ idDot ];
     }
       return true;      
}
function  viewDeletePolygons( responseData ){
     if( responseData.lastDelPolygonId.length == 0 ){ return false; }
}
//------------------------------------------------------
ymaps.ready(function () {
    myMap = new ymaps.Map("YMapsID", {
      center: [49.9412,36.3084],
      zoom: 18
    });
    myPlacemark = new ymaps.Placemark([49.9412,36.3084],{}, { preset: 'islands#redDotIcon'});
    myMap.geoObjects.add(myPlacemark);
});
//---------------------------------------------------- 
document.body.onkeydown = function(event){
	var key = event.keyCode;
	switch (key){
		case 38:
			latitude = latitude + 0.0001 ;
			break;
		case 40:
			latitude = latitude - 0.0001;
			break;
		case 39:
			longitude  = longitude + 0.0001;
			break;
		case 37:
			longitude  = longitude - 0.0001 ;
			break;
		default :
			return false;
	}
	changePosition();
} 	
//----------------------------------------------------
function changePosition(){	
	var parameter = {   'latitude' : latitude , 'longitude' : longitude, 'accuracy' : accuracy , 'speed' : speed, 'idGamer' : idGamer };
	var theParam = 'data=' + JSON.stringify( parameter );
	ajaxPost.setAjaxQuery('http://dots/round/change_position' , theParam , viewNewPosition , 'POST' , 'text');
	//--------------------------
	myPlacemark.editor.startEditing();
	myPlacemark.geometry.setCoordinates( [latitude , longitude] );
	myPlacemark.editor.stopEditing();	
}

function viewNewPosition( responseXMLDocument ){
                alert(responseXMLDocument );
	var response = JSON.parse( responseXMLDocument );
   
}
//============================================================================================================================================================
//Конструктор для ajax GET POST запросов.Возвращает обьект с методом this.setAjaxQuery.
//Вызов запроса: objectName.setAjaxQuery(theUrl,theParam,theFunct,theQuerType,theRespType) , где :
// theUrl      - url для запроса
// theParam    - строка параметров запроса ( "param1=value1&param2=value2" )
// theFunct    - ф-я которая будет обрабатывать результаты запроса
// theQuerType - тип запроса GET или POST
// theRespType - тип ответа : xml или text - какие данные получаем в ф-ю обработчик : responseXML или responseText
function AjaxGETResponse(){
	var myReq = getXMLHttpRequest();
	var cache = [];
	var functionHandler;
	var typeResponse;
	var me = this;
	if ( myReq === false ){
		dispMessageModalWindow("Fatal Error. ошибка создания обьекта XMLHttpRequest",'#D2691E');
		return false;
	}else{
		myReq.upload.onerror = function() {
			dispMessageModalWindow( 'Произошла ошибка при загрузке данных на сервер!','#D2691E' );
		}
		myReq.timeout = 30000; 
		myReq.ontimeout = function() { dispMessageModalWindow( 'Извините, запрос превысил максимальное время','#D2691E' ); }
	} 
	//--------------------------------------------------------------
	this.send = function(){ sendAjaxQuery(); }
	
	this.setAjaxQuery = function(theUrl,theParam,theFunct,theQuerType,theRespType){ 
		if(cache.length < 50){
			cache.push({'url': theUrl,'param':theParam,'funct':theFunct,'typeQuery':theQuerType,'typeResponse':theRespType});
			sendAjaxQuery();
		}
	};
	//======функция====отпр.===ajax===POST===запроса================
	function sendAjaxQuery(){
		if(!cache || cache.length == 0 ) return false;
		if(myReq){
			if(myReq.readyState == 4 || myReq.readyState == 0){
				var cacheEntry = cache.shift(); 
				var theUrl = cacheEntry.url;
				var theParam = cacheEntry.param;
				var theTypeQuery = cacheEntry.typeQuery;
				functionHandler = cacheEntry.funct;
				typeResponse = cacheEntry.typeResponse;
				if(theTypeQuery == 'GET'){
					var strQuery = (theParam.length) ? theUrl + "?" + theParam : theUrl ;
					myReq.open("GET",strQuery,true);
					myReq.onreadystatechange = getAjaxResponse;
					myReq.send();
				}else if(theTypeQuery == 'POST'){									
					myReq.open("POST",theUrl,true);
					myReq.onreadystatechange = getAjaxResponse;
					myReq.setRequestHeader("Content-type","application/x-www-form-urlencoded");
//					myReq.setRequestHeader("Content-length",theParam.length);
//					myReq.setRequestHeader("Connection","close");
					myReq.send(theParam);
				}
			}else{	setTimeout(me.send,1000); }			
		}else{
			dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
		};		
	};
	//-------------------------------------------------------------
	function getAjaxResponse(){
		if(myReq.readyState == 4 ){
			if(myReq.status == 200){
//				alert(myReq.responseText);
				if(typeResponse == 'xml'){
					var theXMLresponseDoc = myReq.responseXML;
					if (!myReq.responseXML || !myReq.responseXML.documentElement) {
						dispMessageModalWindow("Неверная структура документа XML .  " + myReq.responseText);
					}else{
						var firstNodeName = theXMLresponseDoc.childNodes[0].tagName;
						if(firstNodeName == 'response'){
							functionHandler(myReq.responseXML); 
						}else if(firstNodeName == 'error'){
							dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent,'#FFA500');
						}else{ dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent); }
					}
				}else{ 
					functionHandler(myReq.responseText); 
				}				
			}	
		}	
	}
	// ===== Ajax ===создание===обьекта====XMLHttpRequest===========
	function getXMLHttpRequest()
		{
		var req;
		if(window.ActiveXObject){
			try{
				req = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (e){
				req = false;
			}
		}else{
			try{
				req = new XMLHttpRequest();
			}
			catch (e){
				req = false;
			}
		}
		return req;				
	};
	//----------------------------------------------------------------------------------------------------------------------------------------
	function dispMessageModalWindow(messageData,colorData,domElementData){
		var div = document.createElement('div');
		var color = colorData || '#7A96A1';
		var domElement = domElementData || document.body ;
		div.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;color:black;font-sixe:1.3rem;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;background:" + color + ";";
		domElement.insertBefore(div, domElement.firstChild);
		div.innerHTML = messageData;
		div.onclick = function(){ domElement.removeChild(div); }
		return true;
	}	
};
//====================================================================================================================
</script>	
</BODY>
</HTML>
