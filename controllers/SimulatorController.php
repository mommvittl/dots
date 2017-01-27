<?php

namespace app\controllers;
use Yii;
use yii\web\Controller;

ini_set('session.use_only_cookies', true);
if (!isset($_SESSION)) { session_start(); }
// $_SESSION['logg'] = TRUE;

class SimulatorController extends Controller {

    public function actionSimulator() {

        $_SESSION['idGame'] = 2;
        $_SESSION['idGamer'] = 7;
        $_SESSION['idEnemy'] = 6;
        $_SESSION['startTime'] = '2017-01-25 13:16:06';
        ?>
        <!DOCTYPE html>
        <HTML>
            <HEAD>
                <META HTTP-EQUIV="Content-type" CONTENT="TEXT/HTML; charset=utf-8">
                <TITLE>Список сотрудников</TITLE> 
                <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
            </HEAD>
            <BODY>

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

                    var lastDotId = 0;
                    var lastPolygonId = 0;
                    var lastDelDotId = 0;
                    var lastDelPolygonId = 0;

                    var dots = {};
                    var polygons = {};
                    var timerId = setInterval(getNewCommand, 2000);
                    var idPosition = window.navigator.geolocation.watchPosition(successPosition, errorPosition, {enableHighAccuracy: true});
                    //------------------------------------------------------
                    ymaps.ready(function () {
                        myMap = new ymaps.Map("YMapsID", {
                            center: [49.9412, 36.3084],
                            zoom: 18,
                            controls: ["zoomControl", "fullscreenControl"]
                        });
                    /*      var myButton = new ymaps.control.Button({
                            data: {content: 'start / stop' ,  image: "/web/images/Control2.png" },
                            options: {
                                selectOnClick: false,
                                size: 'large',
                                maxWidth: [30, 100, 150]
                            }});
                      myMap.controls.add(myButton, {float: 'left'});
                        myButton.events.add('press', function () {
                            functionNameForMyButClick();
                        });  */
                        myPlacemark = new ymaps.Placemark([49.9412, 36.3084], {iconContent: 'my'}, {preset: 'islands#redStretchyIcon'});
                        myMap.geoObjects.add(myPlacemark);

                        var myCircle = new ymaps.Circle([[49.9412, 36.3084], 100], {}, {fillColor: "#DB709377", strokeColor: "#990066", strokeOpacity: 0.8, strokeWidth: 1});
                        myMap.geoObjects.add(myCircle);
                    });

                    // Цвет обводки.

                    // Прозрачность обводки.

                    // Ширина обводки в пикселях.

                    //----------------------------------------------------
                    function changePosition() {
                        var parameter = [{'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed}];
                        var theParam = JSON.stringify(parameter);
                        ajaxPost.setAjaxQuery('/round/change-position', theParam, viewNewPosition, 'POST', 'text');
                        //--------------------------
                        myPlacemark.editor.startEditing();
                        myPlacemark.geometry.setCoordinates([latitude, longitude]);
                        myPlacemark.editor.stopEditing();
                    }

                    function viewNewPosition(responseXMLDocument) {
                        alert(responseXMLDocument);
                        console.log(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);

                    }
                    //------------------------------------------------------------------------------------------------------------------	
                    function successPosition(position) {
                        newLatitude = position.coords.latitude;
                        newLongitude = position.coords.longitude;
                        newLongitude = position.coords.longitude;
                        newAccuracy = position.coords.accuracy;
                        var x = Math.pow(newLatitude - latitude, 2);
                        var y = Math.pow(newLongitude - longitude, 2);
                        var d = Math.sqrt(x + y);
                        //     var dist = newAccuracy + accuracy;
                        //     if ( dist < 20 ){ dist = 20; }
                        var dist = 20;
                        if (d > 0.0000075 * dist) {
                            latitude = newLatitude;
                            longitude = newLongitude;
                            accuracy = newAccuracy;
                            console.log(newLatitude + ' , ' + newLongitude + ' , ' + newAccuracy);
                            changePosition();
                        }
                    }
                    ;
                    function errorPosition(obj) {
                        alert("Ошибка при определении положения");
                    }
                    ;
                    function updatePos(newLatitude, newLongitude, newAccuracy) {
                    }
                    //----------------------------------------------------
                    function getNewCommand() {
                        var theParam = JSON.stringify({'lastDotId': lastDotId, 'lastPolygonId': lastPolygonId, 'lastDelDotId': lastDelDotId, 'lastDelPolygonId': lastDelPolygonId});
                        ajaxGet.setAjaxQuery('/round/get-change', theParam, getResponseScript, 'POST', 'text');
                    }
                    function getResponseScript(responseXMLDocument) {
                        alert(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);
                        viewAddDots(response);
                        viewAddPolygons(response);
                        viewDeleteDots(response);
                        viewDeletePolygons(response);
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
                    function  viewAddDots(responseData) {
                        if (responseData.arrAddDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddDots.length; i++) {
                            var gamerColor = (responseData.arrAddDots[ i ].gamer == 'me') ? "#F4A46077" : "#87CEFA77";
                            //  var gamerColor = ( responseData.arrAddDots[ i ].gamer == 'me'  ) ? 'islands#darkOrangeCircleDotIcon' : 'islands#blueCircleDotIcon' ;
                            lastDotId = responseData.arrAddDots[ i ].id;
                            dots[ lastDotId ] = new ymaps.Circle([[responseData.arrAddDots[ i ].latitude, responseData.arrAddDots[ i ].longitude], responseData.arrAddDots[ i ].accuracy], {}, {fillColor: gamerColor, strokeColor: "#990066", strokeOpacity: 0.8, strokeWidth: 1});
                            //     dots[ lastDotId ] = new ymaps.Placemark( [ responseData.arrAddDots[ i ].latitude , responseData.arrAddDots[ i ].longitude ], { }, { preset: gamerColor  } );	
                            myMap.geoObjects.add(dots[ lastDotId ]);
                        }
                        return true;
                    }
                    function  viewAddPolygons(responseData) {
                        if (responseData.arrAddPolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddPolygon.length; i++) {
                            var newRing = [];
                            var poligon = responseData.arrAddPolygon[ i ].arrDot;
                            for (var j = 0; j < poligon.length; j++) {
                                newRing.push([poligon[ j ].latitude, poligon[ j ].longitude]);
                            }
                            var gamerColor = (responseData.arrAddPolygon[ i ].gamer == 'me') ? '#FFA500' : '#87CEEB';
                            lastPolygonId = responseData.arrAddPolygon[ i ].id;
                            polygons[ lastPolygonId ] = new ymaps.Polygon(
                                          //       [newRing],
                                      [ poligon ],  
                                    {hintContent: "Многоугольник"},
                                    {fillColor: gamerColor, strokeWidth: 8, opacity: 0.5}
                            );
                            myMap.geoObjects.add(polygons[ lastPolygonId ]);
                        }
                    }
                    function  viewDeleteDots(responseData) {
                        if (responseData.arrIdDeleteDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeleteDots.length; i++) {
                            var idDot = responseData.arrIdDeleteDots[ i ].id;
                            myMap.geoObjects.remove(dots[ idDot ]);
                            delete dots[ idDot ];
                        }
                        return true;
                    }
                    function  viewDeletePolygons(responseData) {
                        if (responseData.arrIdDeletePolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeletePolygon.length; i++) {
                            var idPoly = responseData.arrIdDeletePolygon[ i ].id;
                            myMap.geoObjects.remove(polygons[ idPoly ]);
                            delete polygons[ idPoly ];
                        }
                        return true;
                    }
                    //---------------------------------------------------- 
                    document.body.onkeydown = function (event) {
                        var key = event.keyCode;
                        switch (key) {
                            case 38:
                                latitude = latitude + 0.0003;
                                break;
                            case 40:
                                latitude = latitude - 0.0003;
                                break;
                            case 39:
                                longitude = longitude + 0.0003;
                                break;
                            case 37:
                                longitude = longitude - 0.0003;
                                break;
                            default :
                                return false;
                        }
                        changePosition();
                    }
                    //============================================================================================================================================================
                    //Конструктор для ajax GET POST запросов.Возвращает обьект с методом this.setAjaxQuery.
                    //Вызов запроса: objectName.setAjaxQuery(theUrl,theParam,theFunct,theQuerType,theRespType) , где :
                    // theUrl      - url для запроса
                    // theParam    - строка параметров запроса ( "param1=value1&param2=value2" )
                    // theFunct    - ф-я которая будет обрабатывать результаты запроса
                    // theQuerType - тип запроса GET или POST
                    // theRespType - тип ответа : xml или text - какие данные получаем в ф-ю обработчик : responseXML или responseText
                    function AjaxGETResponse() {
                        var myReq = getXMLHttpRequest();
                        var cache = [];
                        var functionHandler;
                        var typeResponse;
                        var me = this;
                        if (myReq === false) {
                            dispMessageModalWindow("Fatal Error. ошибка создания обьекта XMLHttpRequest", '#D2691E');
                            return false;
                        } else {
                            myReq.upload.onerror = function () {
                                dispMessageModalWindow('Произошла ошибка при загрузке данных на сервер!', '#D2691E');
                            }
                            myReq.timeout = 30000;
                            myReq.ontimeout = function () {
                                dispMessageModalWindow('Извините, запрос превысил максимальное время', '#D2691E');
                            }
                        }
                        //--------------------------------------------------------------
                        this.send = function () {
                            sendAjaxQuery();
                        }

                        this.setAjaxQuery = function (theUrl, theParam, theFunct, theQuerType, theRespType) {
                            if (cache.length < 50) {
                                cache.push({'url': theUrl, 'param': theParam, 'funct': theFunct, 'typeQuery': theQuerType, 'typeResponse': theRespType});
                                sendAjaxQuery();
                            }
                        };
                        //======функция====отпр.===ajax===POST===запроса================
                        function sendAjaxQuery() {
                            if (!cache || cache.length == 0)
                                return false;
                            if (myReq) {
                                if (myReq.readyState == 4 || myReq.readyState == 0) {
                                    var cacheEntry = cache.shift();
                                    var theUrl = cacheEntry.url;
                                    var theParam = cacheEntry.param;
                                    var theTypeQuery = cacheEntry.typeQuery;
                                    functionHandler = cacheEntry.funct;
                                    typeResponse = cacheEntry.typeResponse;
                                    if (theTypeQuery == 'GET') {
                                        var strQuery = (theParam.length) ? theUrl + "?" + theParam : theUrl;
                                        myReq.open("GET", strQuery, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.send();
                                    } else if (theTypeQuery == 'POST') {
                                        myReq.open("POST", theUrl, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                        //					myReq.setRequestHeader("Content-length",theParam.length);
                                        //					myReq.setRequestHeader("Connection","close");
                                        myReq.send(theParam);
                                    }
                                } else {
                                    setTimeout(me.send, 1000);
                                }
                            } else {
                                dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
                            }
                            ;
                        }
                        ;
                        //-------------------------------------------------------------
                        function getAjaxResponse() {
                            if (myReq.readyState == 4) {
                                if (myReq.status == 200) {
                                    //				alert(myReq.responseText);
                                    if (typeResponse == 'xml') {
                                        var theXMLresponseDoc = myReq.responseXML;
                                        if (!myReq.responseXML || !myReq.responseXML.documentElement) {
                                            dispMessageModalWindow("Неверная структура документа XML .  " + myReq.responseText);
                                        } else {
                                            var firstNodeName = theXMLresponseDoc.childNodes[0].tagName;
                                            if (firstNodeName == 'response') {
                                                functionHandler(myReq.responseXML);
                                            } else if (firstNodeName == 'error') {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent, '#FFA500');
                                            } else {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent);
                                            }
                                        }
                                    } else {
                                        functionHandler(myReq.responseText);
                                    }
                                }
                            }
                        }
                        // ===== Ajax ===создание===обьекта====XMLHttpRequest===========
                        function getXMLHttpRequest()
                        {
                            var req;
                            if (window.ActiveXObject) {
                                try {
                                    req = new ActiveXObject("Microsoft.XMLHTTP");
                                } catch (e) {
                                    req = false;
                                }
                            } else {
                                try {
                                    req = new XMLHttpRequest();
                                } catch (e) {
                                    req = false;
                                }
                            }
                            return req;
                        }
                        ;
                        //----------------------------------------------------------------------------------------------------------------------------------------
                        function dispMessageModalWindow(messageData, colorData, domElementData) {
                            var div = document.createElement('div');
                            var color = colorData || '#7A96A1';
                            var domElement = domElementData || document.body;
                            div.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;color:black;font-sixe:1.3rem;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;background:" + color + ";";
                            domElement.insertBefore(div, domElement.firstChild);
                            div.innerHTML = messageData;
                            div.onclick = function () {
                                domElement.removeChild(div);
                            }
                            return true;
                        }
                    }
                    ;
                    //====================================================================================================================
                </script>	
            </BODY>
        </HTML>

        <?php
    }

    public function actionSimulator2() {


        $_SESSION['idGame'] = 2;
        $_SESSION['idGamer'] = 6;
        $_SESSION['idEnemy'] = 7;
        $_SESSION['startTime'] = '2017-01-25 13:16:06';
        ?>
        <!DOCTYPE html>
        <HTML>
            <HEAD>
                <META HTTP-EQUIV="Content-type" CONTENT="TEXT/HTML; charset=utf-8">
                <TITLE>Список сотрудников</TITLE> 
                <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
            </HEAD>
            <BODY>

                <div id="YMapsID" style="width: 1200px; height: 1200px; margin: 0 auto; "></div>

                <script>
                    var ajaxGet = new AjaxGETResponse;
                    var ajaxPost = new AjaxGETResponse;

                    var myMap;
                    var myPlacemark;

                    var latitude = 49.9412902;
                    var longitude = 36.3085217;
                    var accuracy = 10;
                    var speed = 1;

                    var lastDotId = 0;
                    var lastPolygonId = 0;
                    var lastDelDotId = 0;
                    var lastDelPolygonId = 0;

                    var dots = {};
                    var polygons = {};
                    var timerId = setInterval(getNewCommand, 2000);
                    var idPosition = window.navigator.geolocation.watchPosition(successPosition, errorPosition, {enableHighAccuracy: true});
                    //------------------------------------------------------
                    ymaps.ready(function () {
                        myMap = new ymaps.Map("YMapsID", {
                            center: [49.9412, 36.3084],
                            zoom: 18,
                            controls: ["zoomControl", "fullscreenControl"]
                        });
             /*            var myButton = new ymaps.control.Button({
                            data: {content: 'start / stop' ,  image: "/web/images/Control2.png" },
                            options: {
                                selectOnClick: false,
                                size: 'large',
                                maxWidth: [30, 100, 150]
                            }});
                      myMap.controls.add(myButton, {float: 'left'});
                        myButton.events.add('press', function () {
                            functionNameForMyButClick();
                        }); */
                        myPlacemark = new ymaps.Placemark([49.9412, 36.3084], {iconContent: 'my'}, {preset: 'islands#redStretchyIcon'});
                        myMap.geoObjects.add(myPlacemark);
                    });
                    //----------------------------------------------------
                    function changePosition() {
                        var parameter = [{'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed}];
                        var theParam = JSON.stringify(parameter);
                        ajaxPost.setAjaxQuery('/round/change-position', theParam, viewNewPosition, 'POST', 'text');
                        //--------------------------
                        myPlacemark.editor.startEditing();
                        myPlacemark.geometry.setCoordinates([latitude, longitude]);
                        myPlacemark.editor.stopEditing();
                    }

                    function viewNewPosition(responseXMLDocument) {
                        //        alert(responseXMLDocument );
                        console.log(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);

                    }
                    //------------------------------------------------------------------------------------------------------------------	
                    function successPosition(position) {
                        newLatitude = position.coords.latitude;
                        newLongitude = position.coords.longitude;
                        newLongitude = position.coords.longitude;
                        newAccuracy = position.coords.accuracy;
                        var x = Math.pow(newLatitude - latitude, 2);
                        var y = Math.pow(newLongitude - longitude, 2);
                        var d = Math.sqrt(x + y);
                        //     var dist = newAccuracy + accuracy;
                        //     if ( dist < 20 ){ dist = 20; }
                        var dist = 20;
                        if (d > 0.0000075 * dist) {
                            latitude = newLatitude;
                            longitude = newLongitude;
                            accuracy = newAccuracy;
                            console.log(newLatitude + ' , ' + newLongitude + ' , ' + newAccuracy);
                            changePosition();
                        }
                    }
                    ;
                    function errorPosition(obj) {
                        alert("Ошибка при определении положения");
                    }
                    ;
                    function updatePos(newLatitude, newLongitude, newAccuracy) {
                    }
                    //----------------------------------------------------
                    function getNewCommand() {
                        var theParam = JSON.stringify({'lastDotId': lastDotId, 'lastPolygonId': lastPolygonId, 'lastDelDotId': lastDelDotId, 'lastDelPolygonId': lastDelPolygonId});
                        ajaxGet.setAjaxQuery('/round/get-change', theParam, getResponseScript, 'POST', 'text');
                    }
                    function getResponseScript(responseXMLDocument) {
                        alert(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);
                        viewAddDots(response);
                        viewAddPolygons(response);
                        viewDeleteDots(response);
                        viewDeletePolygons(response);
                        lastDelDotId = response.lastDelDotId;
                        lastDelPolygonId = response.lastDelPolygonId;
                    }
                    //------------------------------------------------------
                    function  viewAddDots(responseData) {
                        if (responseData.arrAddDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddDots.length; i++) {
                            var gamerColor = (responseData.arrAddDots[ i ].gamer == 'me') ? "#F4A46077" : "#87CEFA77";
                            //  var gamerColor = ( responseData.arrAddDots[ i ].gamer == 'me'  ) ? 'islands#darkOrangeCircleDotIcon' : 'islands#blueCircleDotIcon' ;
                            dots[ lastDotId ] = new ymaps.Circle([[responseData.arrAddDots[ i ].latitude, responseData.arrAddDots[ i ].longitude], responseData.arrAddDots[ i ].accuracy], {}, {fillColor: gamerColor, strokeColor: "#990066", strokeOpacity: 0.8, strokeWidth: 1});
                            //     dots[ lastDotId ] = new ymaps.Placemark( [ responseData.arrAddDots[ i ].latitude , responseData.arrAddDots[ i ].longitude ], { }, { preset: gamerColor  } );	
                            myMap.geoObjects.add(dots[ lastDotId ]);
                        }
                        return true;
                    }
                    function  viewAddPolygons(responseData) {
                        if (responseData.arrAddPolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddPolygon.length; i++) {
                            var newRing = [];
                            var poligon = responseData.arrAddPolygon[ i ].arrDot;
                            for (var j = 0; j < poligon.length; j++) {
                                newRing.push([poligon[ j ].latitude, poligon[ j ].longitude]);
                            }
                            var gamerColor = (responseData.arrAddPolygon[ i ].gamer == 'me') ? '#FFA500' : '#87CEEB';
                            lastPolygonId = responseData.arrAddPolygon[ i ].id;
                            polygons[ lastPolygonId ] = new ymaps.Polygon(
                                            //       [newRing],
                                      [ poligon ],  
                                    {hintContent: "Многоугольник"},
                                    {fillColor: gamerColor, strokeWidth: 8, opacity: 0.5}
                            );
                            myMap.geoObjects.add(polygons[ lastPolygonId ]);
                        }
                    }
                    function  viewDeleteDots(responseData) {
                        if (responseData.arrIdDeleteDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeleteDots.length; i++) {
                            var idDot = responseData.arrIdDeleteDots[ i ].id;
                            myMap.geoObjects.remove(dots[ idDot ]);
                            delete dots[ idDot ];
                        }
                        return true;
                    }
                    function  viewDeletePolygons(responseData) {
                        if (responseData.arrIdDeletePolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeletePolygon.length; i++) {
                            var idPoly = responseData.arrIdDeletePolygon[ i ].id;
                            myMap.geoObjects.remove(polygons[ idPoly ]);
                            delete polygons[ idPoly ];
                        }
                        return true;
                    }
                    //---------------------------------------------------- 
                    document.body.onkeydown = function (event) {
                        var key = event.keyCode;
                        switch (key) {
                            case 38:
                                latitude = latitude + 0.0001;
                                break;
                            case 40:
                                latitude = latitude - 0.0001;
                                break;
                            case 39:
                                longitude = longitude + 0.0001;
                                break;
                            case 37:
                                longitude = longitude - 0.0001;
                                break;
                            default :
                                return false;
                        }
                        changePosition();
                    }
                    //============================================================================================================================================================
                    //Конструктор для ajax GET POST запросов.Возвращает обьект с методом this.setAjaxQuery.
                    //Вызов запроса: objectName.setAjaxQuery(theUrl,theParam,theFunct,theQuerType,theRespType) , где :
                    // theUrl      - url для запроса
                    // theParam    - строка параметров запроса ( "param1=value1&param2=value2" )
                    // theFunct    - ф-я которая будет обрабатывать результаты запроса
                    // theQuerType - тип запроса GET или POST
                    // theRespType - тип ответа : xml или text - какие данные получаем в ф-ю обработчик : responseXML или responseText
                    function AjaxGETResponse() {
                        var myReq = getXMLHttpRequest();
                        var cache = [];
                        var functionHandler;
                        var typeResponse;
                        var me = this;
                        if (myReq === false) {
                            dispMessageModalWindow("Fatal Error. ошибка создания обьекта XMLHttpRequest", '#D2691E');
                            return false;
                        } else {
                            myReq.upload.onerror = function () {
                                dispMessageModalWindow('Произошла ошибка при загрузке данных на сервер!', '#D2691E');
                            }
                            myReq.timeout = 30000;
                            myReq.ontimeout = function () {
                                dispMessageModalWindow('Извините, запрос превысил максимальное время', '#D2691E');
                            }
                        }
                        //--------------------------------------------------------------
                        this.send = function () {
                            sendAjaxQuery();
                        }

                        this.setAjaxQuery = function (theUrl, theParam, theFunct, theQuerType, theRespType) {
                            if (cache.length < 50) {
                                cache.push({'url': theUrl, 'param': theParam, 'funct': theFunct, 'typeQuery': theQuerType, 'typeResponse': theRespType});
                                sendAjaxQuery();
                            }
                        };
                        //======функция====отпр.===ajax===POST===запроса================
                        function sendAjaxQuery() {
                            if (!cache || cache.length == 0)
                                return false;
                            if (myReq) {
                                if (myReq.readyState == 4 || myReq.readyState == 0) {
                                    var cacheEntry = cache.shift();
                                    var theUrl = cacheEntry.url;
                                    var theParam = cacheEntry.param;
                                    var theTypeQuery = cacheEntry.typeQuery;
                                    functionHandler = cacheEntry.funct;
                                    typeResponse = cacheEntry.typeResponse;
                                    if (theTypeQuery == 'GET') {
                                        var strQuery = (theParam.length) ? theUrl + "?" + theParam : theUrl;
                                        myReq.open("GET", strQuery, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.send();
                                    } else if (theTypeQuery == 'POST') {
                                        myReq.open("POST", theUrl, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                        //					myReq.setRequestHeader("Content-length",theParam.length);
                                        //					myReq.setRequestHeader("Connection","close");
                                        myReq.send(theParam);
                                    }
                                } else {
                                    setTimeout(me.send, 1000);
                                }
                            } else {
                                dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
                            }
                            ;
                        }
                        ;
                        //-------------------------------------------------------------
                        function getAjaxResponse() {
                            if (myReq.readyState == 4) {
                                if (myReq.status == 200) {
                                    //				alert(myReq.responseText);
                                    if (typeResponse == 'xml') {
                                        var theXMLresponseDoc = myReq.responseXML;
                                        if (!myReq.responseXML || !myReq.responseXML.documentElement) {
                                            dispMessageModalWindow("Неверная структура документа XML .  " + myReq.responseText);
                                        } else {
                                            var firstNodeName = theXMLresponseDoc.childNodes[0].tagName;
                                            if (firstNodeName == 'response') {
                                                functionHandler(myReq.responseXML);
                                            } else if (firstNodeName == 'error') {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent, '#FFA500');
                                            } else {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent);
                                            }
                                        }
                                    } else {
                                        functionHandler(myReq.responseText);
                                    }
                                }
                            }
                        }
                        // ===== Ajax ===создание===обьекта====XMLHttpRequest===========
                        function getXMLHttpRequest()
                        {
                            var req;
                            if (window.ActiveXObject) {
                                try {
                                    req = new ActiveXObject("Microsoft.XMLHTTP");
                                } catch (e) {
                                    req = false;
                                }
                            } else {
                                try {
                                    req = new XMLHttpRequest();
                                } catch (e) {
                                    req = false;
                                }
                            }
                            return req;
                        }
                        ;
                        //----------------------------------------------------------------------------------------------------------------------------------------
                        function dispMessageModalWindow(messageData, colorData, domElementData) {
                            var div = document.createElement('div');
                            var color = colorData || '#7A96A1';
                            var domElement = domElementData || document.body;
                            div.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;color:black;font-sixe:1.3rem;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;background:" + color + ";";
                            domElement.insertBefore(div, domElement.firstChild);
                            div.innerHTML = messageData;
                            div.onclick = function () {
                                domElement.removeChild(div);
                            }
                            return true;
                        }
                    }
                    ;
                    //====================================================================================================================
                </script>	
            </BODY>
        </HTML>
        <?php
    }

    public function actionSimulator3() {

        var_dump($_SESSION);
        ?>

        <!DOCTYPE html>
        <HTML>
            <HEAD>
                <META HTTP-EQUIV="Content-type" CONTENT="TEXT/HTML; charset=utf-8">
                <TITLE>Список сотрудников</TITLE> 
                <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
            </HEAD>
            <BODY>
                <h1>JS Test debugger</h1>
                <p><button type="button" id="startBut">Start</button><button type="button" id="readyBut">Ready</button>
                    <button type="button" id="unReady">unReady</button><button type="button" id="selEnemy">selEnemy</button>
                    <INPUT type="number" min="1" id="numEmemy" name="numEmemy"></input><button type="button" id="gameOver">gameOver</button></p>

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
                    var idGamer = 2;
                    var idEnemy = 1;

                    var lastDotId = 0;
                    var lastPolygonId = 0;
                    var lastDelDotId = 0;
                    var lastDelPolygonId = 0;

                    var dots = {};
                    var polygons = {};
                    //var timerId = setInterval( getNewCommand, 1000 );
                    //------------------------------------------------------
                    var start = document.getElementById('startBut');
                    start.onclick = getNewCommand;

                    var ready = document.getElementById('readyBut');
                    ready.onclick = getReadyCommand;

                    var unReady = document.getElementById('unReady');
                    unReady.onclick = unReadyCommand;

                    var selEnemy = document.getElementById('selEnemy');
                    selEnemy.onclick = selEnemyCommand;

                    var gameOver = document.getElementById('gameOver');
                    gameOver.onclick = gameOverCommand;
                    //----------------------------------------------------------
                    function getReadyCommand() {
                        var theParam = JSON.stringify({'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed, 'idGamer': idGamer});
                        ajaxGet.setAjaxQuery('http://dots/ruling/get-ready', theParam, viewGetReady, 'POST', 'text');
                    }
                    function viewGetReady(responseXMLDocument) {
                        alert(responseXMLDocument);
                    }
                    // ---------------------------------------------------
                    function unReadyCommand() {
                        var theParam = JSON.stringify({'idGamer': idGamer});
                        ajaxGet.setAjaxQuery('/ruling/stop-ready', theParam, viewStopReady, 'POST', 'text');
                    }
                    function viewStopReady(responseXMLDocument) {
                        alert(responseXMLDocument);
                    }
                    // ---------------------------------------------------
                    function selEnemyCommand() {
                        var num = document.getElementById('numEmemy').value;
                        var theParam = JSON.stringify({'idGamer': idGamer, 'idEnemy': num});
                        ajaxGet.setAjaxQuery('/ruling/enemy-selection', theParam, viewSelEnemy, 'POST', 'text');
                    }
                    function viewSelEnemy(responseXMLDocument) {
                        alert(responseXMLDocument);
                    }
                    // ---------------------------------------------------
                    function gameOverCommand() {
                        alert('erererre');
                        var theParam = JSON.stringify({'idGamer': idGamer});
                        ajaxGet.setAjaxQuery('/ruling/stop-game', theParam, viewSelEnemy, 'POST', 'text');
                    }
                    function viewSelEnemy(responseXMLDocument) {
                        alert(responseXMLDocument);
                    }
                    //----------------------------------------------------
                    function getNewCommand() {
                        var theParam = JSON.stringify({'lastDotId': lastDotId, 'lastPolygonId': lastPolygonId, 'lastDelDotId': lastDelDotId, 'lastDelPolygonId': lastDelPolygonId, 'idGamer': idGamer});
                        ajaxGet.setAjaxQuery('/round/get-change', theParam, getResponseScript, 'POST', 'text');
                    }
                    function getResponseScript(responseXMLDocument) {
                        alert(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);
                        viewAddDots(response);
                        viewAddPolygons(response);
                        viewDeleteDots(response);
                        viewDeletePolygons(response);
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
                    function  viewAddDots(responseData) {
                        if (responseData.arrAddDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddDots.length; i++) {
                            var gamerColor = (responseData.arrAddDots[ i ].gamer == 'me') ? 'islands#darkOrangeCircleDotIcon' : 'islands#blueCircleDotIcon';
                            lastDotId = responseData.arrAddDots[ i ].id;
                            dots[ lastDotId ] = new ymaps.Placemark([responseData.arrAddDots[ i ].latitude, responseData.arrAddDots[ i ].longitude], {}, {preset: gamerColor});
                            myMap.geoObjects.add(dots[ lastDotId ]);
                        }
                        return true;
                    }
                    function  viewAddPolygons(responseData) {
                        if (responseData.arrAddPolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddPolygon.length; i++) {
                            var newRing = [];
                            var poligon = responseData.arrAddPolygon[ i ].arrDot;
                            for (var j = 0; j < poligon.length; j++) {
                                newRing.push([poligon[ j ].latitude, poligon[ j ].longitude]);
                            }
                            var gamerColor = (responseData.arrAddPolygon[ i ].gamer == 'me') ? '#FFA500' : '#87CEEB';
                            lastPolygonId = responseData.arrAddPolygon[ i ].id;
                            polygons[ lastPolygonId ] = new ymaps.Polygon(
                                         //       [newRing],
                                      [ poligon ],  
                                    {hintContent: "Многоугольник"},
                                    {fillColor: gamerColor, strokeWidth: 8, opacity: 0.5}
                            );
                            myMap.geoObjects.add(polygons[ lastPolygonId ]);
                        }
                    }
                    function  viewDeleteDots(responseData) {
                        if (responseData.arrIdDeleteDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeleteDots.length; i++) {
                            var idDot = responseData.arrIdDeleteDots[ i ].id;
                            myMap.geoObjects.remove(dots[ idDot ]);
                            delete dots[ idDot ];
                        }
                        return true;
                    }
                    function  viewDeletePolygons(responseData) {
                        if (responseData.arrIdDeletePolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeletePolygon.length; i++) {
                            var idPoly = responseData.arrIdDeletePolygon[ i ].id;
                            myMap.geoObjects.remove(polygons[ idPoly ]);
                            delete polygons[ idPoly ];
                        }
                        return true;
                    }
                    //------------------------------------------------------
                    ymaps.ready(function () {
                        myMap = new ymaps.Map("YMapsID", {
                            center: [49.9412, 36.3084],
                            zoom: 18
                        });
                        myPlacemark = new ymaps.Placemark([49.9412, 36.3084], {}, {preset: 'islands#redDotIcon'});
                        myMap.geoObjects.add(myPlacemark);
                    });
                    //---------------------------------------------------- 
                    document.body.onkeydown = function (event) {
                        var key = event.keyCode;
                        switch (key) {
                            case 38:
                                latitude = latitude + 0.0003;
                                break;
                            case 40:
                                latitude = latitude - 0.0003;
                                break;
                            case 39:
                                longitude = longitude + 0.0003;
                                break;
                            case 37:
                                longitude = longitude - 0.0003;
                                break;
                            default :
                                return false;
                        }
                        changePosition();
                    }
                    //----------------------------------------------------
                    function changePosition() {
                        var parameter = [{'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed, 'idGamer': idGamer, 'idEnemy': idEnemy}];
                        var theParam = JSON.stringify(parameter);
                        ajaxPost.setAjaxQuery('/round/change-position', theParam, viewNewPosition, 'POST', 'text');
                        //--------------------------
                        myPlacemark.editor.startEditing();
                        myPlacemark.geometry.setCoordinates([latitude, longitude]);
                        myPlacemark.editor.stopEditing();
                    }

                    function viewNewPosition(responseXMLDocument) {
                        alert(responseXMLDocument);
                        console.log(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);

                    }
                    //============================================================================================================================================================
                    //Конструктор для ajax GET POST запросов.Возвращает обьект с методом this.setAjaxQuery.
                    //Вызов запроса: objectName.setAjaxQuery(theUrl,theParam,theFunct,theQuerType,theRespType) , где :
                    // theUrl      - url для запроса
                    // theParam    - строка параметров запроса ( "param1=value1&param2=value2" )
                    // theFunct    - ф-я которая будет обрабатывать результаты запроса
                    // theQuerType - тип запроса GET или POST
                    // theRespType - тип ответа : xml или text - какие данные получаем в ф-ю обработчик : responseXML или responseText
                    function AjaxGETResponse() {
                        var myReq = getXMLHttpRequest();
                        var cache = [];
                        var functionHandler;
                        var typeResponse;
                        var me = this;
                        if (myReq === false) {
                            dispMessageModalWindow("Fatal Error. ошибка создания обьекта XMLHttpRequest", '#D2691E');
                            return false;
                        } else {
                            myReq.upload.onerror = function () {
                                dispMessageModalWindow('Произошла ошибка при загрузке данных на сервер!', '#D2691E');
                            }
                            myReq.timeout = 30000;
                            myReq.ontimeout = function () {
                                dispMessageModalWindow('Извините, запрос превысил максимальное время', '#D2691E');
                            }
                        }
                        //--------------------------------------------------------------
                        this.send = function () {
                            sendAjaxQuery();
                        }

                        this.setAjaxQuery = function (theUrl, theParam, theFunct, theQuerType, theRespType) {
                            if (cache.length < 50) {
                                cache.push({'url': theUrl, 'param': theParam, 'funct': theFunct, 'typeQuery': theQuerType, 'typeResponse': theRespType});
                                sendAjaxQuery();
                            }
                        };
                        //======функция====отпр.===ajax===POST===запроса================
                        function sendAjaxQuery() {
                            if (!cache || cache.length == 0)
                                return false;
                            if (myReq) {
                                if (myReq.readyState == 4 || myReq.readyState == 0) {
                                    var cacheEntry = cache.shift();
                                    var theUrl = cacheEntry.url;
                                    var theParam = cacheEntry.param;
                                    var theTypeQuery = cacheEntry.typeQuery;
                                    functionHandler = cacheEntry.funct;
                                    typeResponse = cacheEntry.typeResponse;
                                    if (theTypeQuery == 'GET') {
                                        var strQuery = (theParam.length) ? theUrl + "?" + theParam : theUrl;
                                        myReq.open("GET", strQuery, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.send();
                                    } else if (theTypeQuery == 'POST') {
                                        myReq.open("POST", theUrl, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                        //					myReq.setRequestHeader("Content-length",theParam.length);
                                        //					myReq.setRequestHeader("Connection","close");
                                        myReq.send(theParam);
                                    }
                                } else {
                                    setTimeout(me.send, 1000);
                                }
                            } else {
                                dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
                            }
                            ;
                        }
                        ;
                        //-------------------------------------------------------------
                        function getAjaxResponse() {
                            if (myReq.readyState == 4) {
                                if (myReq.status == 200) {
                                    //				alert(myReq.responseText);
                                    if (typeResponse == 'xml') {
                                        var theXMLresponseDoc = myReq.responseXML;
                                        if (!myReq.responseXML || !myReq.responseXML.documentElement) {
                                            dispMessageModalWindow("Неверная структура документа XML .  " + myReq.responseText);
                                        } else {
                                            var firstNodeName = theXMLresponseDoc.childNodes[0].tagName;
                                            if (firstNodeName == 'response') {
                                                functionHandler(myReq.responseXML);
                                            } else if (firstNodeName == 'error') {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent, '#FFA500');
                                            } else {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent);
                                            }
                                        }
                                    } else {
                                        functionHandler(myReq.responseText);
                                    }
                                }
                            }
                        }
                        // ===== Ajax ===создание===обьекта====XMLHttpRequest===========
                        function getXMLHttpRequest()
                        {
                            var req;
                            if (window.ActiveXObject) {
                                try {
                                    req = new ActiveXObject("Microsoft.XMLHTTP");
                                } catch (e) {
                                    req = false;
                                }
                            } else {
                                try {
                                    req = new XMLHttpRequest();
                                } catch (e) {
                                    req = false;
                                }
                            }
                            return req;
                        }
                        ;
                        //----------------------------------------------------------------------------------------------------------------------------------------
                        function dispMessageModalWindow(messageData, colorData, domElementData) {
                            var div = document.createElement('div');
                            var color = colorData || '#7A96A1';
                            var domElement = domElementData || document.body;
                            div.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;color:black;font-sixe:1.3rem;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;background:" + color + ";";
                            domElement.insertBefore(div, domElement.firstChild);
                            div.innerHTML = messageData;
                            div.onclick = function () {
                                domElement.removeChild(div);
                            }
                            return true;
                        }
                    }
                    ;
                    //====================================================================================================================
                </script>	
            </BODY>
        </HTML>
        <?php
    }

    //=============================================================================   
    //=============================================================================   
    //=============================================================================   
    //=============================================================================   
    //=============================================================================   
    //=============================================================================   
    public function actionSimulator4() {
        $session = Yii::$app->session;
        if (!$session->isActive) {  exit;  }      
        $idGame = ( isset($session['idGame']) ) ? $session['idGame'] : 0;
        $idGamer = ( isset($session['idGamer']) ) ? $session['idGamer'] : 0;
        $idEnemy = ( isset($session['idEnemy']) ) ? $session['idEnemy'] : 0;
        $log = ( isset($session['logg']) && ($session['logg'] === true ) ) ? "Вы залогинены" : "Вы не залогинены";
        $strStartMess = ( $idGame ) ? "You are in the game. Для завершения игры нажмите stop" : "Нажмите start для поиска соперников" ;
        $startGameStatus = ( $idGame ) ? " 'game' " : " 'noGame' " ;
        ?>
        <!DOCTYPE html>
        <HTML>
            <HEAD>
                <META HTTP-EQUIV="Content-type" CONTENT="TEXT/HTML; charset=utf-8">             
                <TITLE>Список сотрудников</TITLE> 
                <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
            </HEAD>
            <BODY>              
                <div id="YMapsID" style="  height: 100vh; position: relative; margin: 0 auto;  ">
                     <BUTTON type="button" id="gameControl" style=" position: absolute; left: 10px; top: 10px; z-index: 1000; padding: 5px;width: 25vw; height: 10vh; cursor: pointer; background: #FF7F50; font-size: 40px; ">Start/Stop</BUTTON>
                     <div id="informStr" style="position: absolute; right: 10px; top: 5px; z-index: 1000; padding: 5px; width: 70vw; background: black; color: white; font: 40px/50px arial; text-align: center;"><?php echo $strStartMess ?></div>
                </div>
                <script>
                                                                                
        
                    var startStatus =  <?php echo $startGameStatus ?>                                                                       
                    var ajaxGet = new AjaxGETResponse;
                    var ajaxPost = new AjaxGETResponse;

                    var myMap;
                    var myPlacemark;

                    var latitude = 49.9412902;
                    var longitude = 36.3085217;
                    var accuracy = 10;
                    var speed = 1;

                    var lastDotId;
                    var lastPolygonId;
                    var lastDelDotId;
                    var lastDelPolygonId;

                    var dots;
                    var polygons;
                    var collectionOpponents;
                    var functionNameForMyButClick;

                    var timerId = setInterval(getNewCommand, 2000);
                    var idPosition = window.navigator.geolocation.watchPosition(successPosition, errorPosition, {enableHighAccuracy: true});
                    initializationVar();
                    functionNameForMyButClick = ( startStatus == 'game' ) ? stopGame : findGetReady ;
                    var gameControl = document.getElementById( 'gameControl' );
                    gameControl.onclick =  function () { functionNameForMyButClick();  }                  
                    //------------------------------------------------------
                    ymaps.ready(function () {
                        myMap = new ymaps.Map("YMapsID", {
                            center: [49.94, 36.30],
                            zoom: 18,
                            controls: ["zoomControl"]
                        });
                        myPlacemark = new ymaps.Placemark([49.94, 36.30], {iconContent: 'my'}, {preset: 'islands#redStretchyIcon'});
                        myMap.geoObjects.add(myPlacemark);
                        var idStartPosition = window.navigator.geolocation.getCurrentPosition(getStartPosition, errorPosition, {enableHighAccuracy: true});
                    });
                    function getStartPosition(position) {
                        latitude = position.coords.latitude;
                        longitude = position.coords.longitude;
                        accuracy = position.coords.accuracy;
                        moveMyPos(latitude, longitude);
                        myMap.setCenter([latitude, longitude]);
                    }
                    function moveMyPos(latData, lonData) {
                        myPlacemark.editor.startEditing();
                        myPlacemark.geometry.setCoordinates([latData, lonData]);
                        myPlacemark.editor.stopEditing();
                    }
                    function initializationVar() {
                        lastDotId = 0;
                        lastPolygonId = 0;
                        lastDelDotId = 0;
                        lastDelPolygonId = 0;
                        dots = {};
                        polygons = {};
                        collectionOpponents = [];
                        functionNameForMyButClick = emptyFunction;
                    }
                    //----------------------------------------------------
                    function changePosition() {
                        var parameter = [{'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed}];
                        var theParam = JSON.stringify(parameter);
                        ajaxPost.setAjaxQuery('/round/change-position', theParam, viewNewPosition, 'POST', 'text');
                        moveMyPos(latitude, longitude);
                    }
                    function viewNewPosition(responseXMLDocument) {
                        //        alert(responseXMLDocument );
                        console.log(responseXMLDocument);
                        var response = JSON.parse(responseXMLDocument);

                    }
                    //------------------------------------------------------------------------------------------------------------------	
                    function successPosition(position) {
                        newLatitude = position.coords.latitude;
                        newLongitude = position.coords.longitude;
                        newAccuracy = position.coords.accuracy;
                        var dist = getDistanse( latitude, longitude, newLatitude, newLongitude );
                        radiusAcc = ( newAccuracy > 20  ) ? newAccuracy : 20 ;
                        if ( dist >  radiusAcc ) {
                            latitude = newLatitude;
                            longitude = newLongitude;
                            accuracy = newAccuracy;
                            console.log(newLatitude + ' , ' + newLongitude + ' , ' + newAccuracy);
                            changePosition();
                        }
                    }
                   
                    function errorPosition(obj) {
                        alert("Ошибка при определении положения");
                    }
                    // ---------------------------------------------------------------------------------------------------------------------
                    function getDistanse( lat, lon, newLat, newLon ){
                        var x = Math.pow(newLat - lat, 2);
                        var y = Math.pow(newLon - lon, 2);
                        var d = Math.sqrt(x + y) /  0.0000075;                     
                        return Math.round( d );
                    }
                    //-----------------------------------------------------------------------------------------------------------------------
                    function findGetReady() {
                        functionNameForMyButClick = stopReadyCommand;
                        getReadyCommand();
                    }
                    //------------------------------------------------------------------------------------------------------------------------
                    function getReadyCommand() {
                        if (functionNameForMyButClick != stopReadyCommand){ return false; }   
                         document.getElementById('informStr').innerHTML = " Поиск игроков.";
                        var theParam = JSON.stringify({'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed});
                        ajaxGet.setAjaxQuery('/ruling/get-ready', theParam, viewGetReady, 'POST', 'text');
                    }

                    function viewGetReady(responseXMLDocument) {
                        var response = JSON.parse(responseXMLDocument);
                        var idEnemy = response.opponent;
                        var idGame = response.idGame;
                        var enemyNic = response.enemyNic
                        var arrOpponents = response.arrOpponents;
                        removeCollectionOpponents()
                        if (idEnemy == 0 && idGame == 0) {
                            var len = arrOpponents.length;
                            document.getElementById('informStr').innerHTML = " Найдено  " + len + " игроков со статусом Ready.";
                            for (var i = 0; i < len; i++) {
                                var dist = getDistanse( latitude, longitude, arrOpponents[ i ].latitude, arrOpponents[ i ].longitude ); 
                                var gamerColor = ( dist > 1000  ) ? 'islands#grayStretchyIcon' : 'islands#darkGreenStretchyIcon' ;
                                var cont = arrOpponents[ i ].nick + " dist:  " + dist + " m." ;
                                collectionOpponents[i] = new ymaps.Placemark([arrOpponents[ i ].latitude, arrOpponents[ i ].longitude], { iconContent: cont }, { preset: gamerColor });
                                if ( dist <= 1000 ){  collectionOpponents[i].events.add('click', selEnemyCommand.bind(arrOpponents[ i ])); }                                
                                myMap.geoObjects.add(collectionOpponents[i]);
                            }
                            setTimeout(getReadyCommand, 10000);
                        } else {
                            removeCollectionOpponents();
                            document.getElementById('informStr').innerHTML = " Game Start: " + idEnemy + " Противник: " + enemyNic;
                            var stringInform =   " Старт игры <br> Ваш противник:  " + enemyNic ;
                            modalInformWindow( stringInform ) ;
                            functionNameForMyButClick = stopGame;
                        }
                    }

                    function removeCollectionOpponents() {
                        for (var i = 0; i < collectionOpponents.length; i++) {
                            myMap.geoObjects.remove(collectionOpponents[i]);
                        }
                        collectionOpponents = [];
                    }
                    // ---------------------------------------------------
                    function selEnemyCommand() {
                         document.getElementById('informStr').innerHTML = " Инициализация игры... " ;
                        var theParam = JSON.stringify({'idEnemy': this.id});
                        ajaxGet.setAjaxQuery('/ruling/enemy-selection', theParam, viewSelEnemy, 'POST', 'text');
                    }
                    function viewSelEnemy(responseXMLDocument) {
                        //   alert(responseXMLDocument);
                    }
                    //----------------------------------------
                    function stopReadyCommand() {
                        removeCollectionOpponents();
                          document.getElementById('informStr').innerHTML = " Отмена статуса Ready... " ;
                        var theParam = JSON.stringify({});
                        ajaxGet.setAjaxQuery('/ruling/stop-ready', theParam, viewStopReady, 'POST', 'text');
                    }
                    function viewStopReady(responseXMLDocument) {
                        functionNameForMyButClick = findGetReady;
                        document.getElementById('informStr').innerHTML = " Нажмите start для поиска игроков ";
                        // alert(responseXMLDocument);
                    }
                    //----------------------------------------------------    
                    function gameOverCommand() {
                        var theParam = JSON.stringify({});
                        ajaxGet.setAjaxQuery('/ruling/stop-game', theParam, viewGameOver, 'POST', 'text');
                    }
                    function viewGameOver(responseXMLDocument) {
                        document.getElementById('informStr').innerHTML = " Game over ";
                        functionNameForMyButClick = emptyFunction;
                        // alert(responseXMLDocument);                    
                    }
                    function emptyFunction() { }
                    //------------------------------------------------------------
                    function stopGame() {
                        var modal = document.createElement('div');
                        modal.innerHTML = "<h1>Вы уверены, что хотите завершить игру?</h1><button id=\"ok_but\">OK</button><button id=\"cancel_but\" autofocus>Cancel</button></p>";
                        document.body.insertBefore(modal, document.body.firstChild);
                        modal.style.cssText = "min-width:500px;max-width: 100%;min-height: 200px;max-height: 100%;cursor:pointer;padding:10px;background:#BDBDBD;color:#3B3C1D;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;"
                        var ok_but = document.getElementById('ok_but');
                        var cancel_but = document.getElementById('cancel_but');
                        ok_but.style.cssText = "border-radius:10px; padding: 10px 20px; background:#FFE4E1; cursor:pointer; outline:none; margin-right: 20px;";
                        cancel_but.style.cssText = "border-radius:10px; padding: 10px 20px; background:#F5F5DC; cursor:pointer; outline:none; margin-left: 20px;";
                        cancel_but.onclick = function () {
                            document.body.removeChild(modal);
                        };
                        ok_but.onclick = function () {
                            document.body.removeChild(modal);
                            gameOverCommand();
                        };
                    }
                    //-------------------------------------------------------------------------------------------------------------------------------
                    function getNewCommand() {
                        var theParam = JSON.stringify({'lastDotId': lastDotId, 'lastPolygonId': lastPolygonId, 'lastDelDotId': lastDelDotId, 'lastDelPolygonId': lastDelPolygonId});
                        ajaxGet.setAjaxQuery('/round/get-change', theParam, getResponseScript, 'POST', 'text');
                    }
                    function getResponseScript(responseXMLDocument) {
                        //  alert(responseXMLDocument );
                        var response = JSON.parse(responseXMLDocument);
                        var status = response.status || 'error';
                        switch (status) {
                            case  'ok' :
                                responseStatusOk(response);
                                break;
                            case 'gameOver' :
                                responseStatusGameOver(response);
                                break;
                            default:
                                responseStatusError(response);
                        }
                    }
                    // ---------------------------------------------------------------------------------------------------------------------
                    function  responseStatusOk(response) {
                        viewAddDots(response);
                        viewAddPolygons(response);
                        viewDeleteDots(response);
                        viewDeletePolygons(response);
                        lastDelDotId = response.lastDelDotId;
                        lastDelPolygonId = response.lastDelPolygonId;
                    }
                    function  responseStatusGameOver(response) {
                        var statusGame = response.message ;
                        var stringInform = "Окончание игры. <br> Победитель " + statusGame.winner + "<br> Ваши очки: " +  statusGame.scoresMe + "<br> очки соперника: " + statusGame.scoresEnemy ;
                   //     alert( mess );
                        modalInformWindow( stringInform ) ;
                        removeGame();
                    }
                    function  responseStatusError(response) {
                      //  alert('Error: ' + response.message)
                    }
                    function removeGame() {
                        for (element in dots) {
                            myMap.geoObjects.remove( dots[ element ] );
                        }
                        for (element in polygons) {
                            myMap.geoObjects.remove( polygons[ element ] );
                        }
                        initializationVar();
                        functionNameForMyButClick = findGetReady;
                        document.getElementById('informStr').innerHTML = " Нажмите start для поиска игроков ";
                        var theParam = JSON.stringify({});
                        ajaxGet.setAjaxQuery('/ruling/remove-session', theParam, emptyFunction, 'POST', 'text');
                    }
                    //------------------------------------------------------
                    function  viewAddDots(responseData) {
                        if (responseData.arrAddDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddDots.length; i++) {
                            var gamerColor = (responseData.arrAddDots[ i ].gamer == 'me') ? "#F4A46077" : "#87CEFA77";
                            var accuracy = ( responseData.arrAddDots[ i ].accuracy < 40 ) ? responseData.arrAddDots[ i ].accuracy : 40 ;
                            lastDotId = responseData.arrAddDots[ i ].id;
                            dots[ lastDotId ] = new ymaps.Circle(
                                    [[responseData.arrAddDots[ i ].latitude, responseData.arrAddDots[ i ].longitude], accuracy ],
                                    {},
                                    {fillColor: gamerColor, strokeOpacity: 0.8, strokeWidth: 1}
                            );
                            myMap.geoObjects.add(dots[ lastDotId ]);
                        }
                        return true;
                    }
                    function  viewAddPolygons(responseData) {
                        if (responseData.arrAddPolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrAddPolygon.length; i++) {
                            var newRing = [];
                            var poligon = responseData.arrAddPolygon[ i ].arrDot;
                            for (var j = 0; j < poligon.length; j++) {
                                newRing.push([poligon[ j ].latitude, poligon[ j ].longitude]);
                            }
                            var gamerColor = (responseData.arrAddPolygon[ i ].gamer == 'me') ? '#FFA500' : '#87CEEB';
                            lastPolygonId = responseData.arrAddPolygon[ i ].id;
                            polygons[ lastPolygonId ] = new ymaps.Polygon(
                             //       [newRing],
                                      [ poligon ],  
                                    {hintContent: "Многоугольник"},
                                    {fillColor: gamerColor, strokeWidth: 8, opacity: 0.5}
                            );
                            myMap.geoObjects.add(polygons[ lastPolygonId ]);
                        }
                    }
                    function  viewDeleteDots(responseData) {
                        if (responseData.arrIdDeleteDots.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeleteDots.length; i++) {
                            var idDot = responseData.arrIdDeleteDots[ i ].id;
                            myMap.geoObjects.remove(dots[ idDot ]);
                            delete dots[ idDot ];
                        }
                        return true;
                    }
                    function  viewDeletePolygons(responseData) {
                        if (responseData.arrIdDeletePolygon.length == 0) {
                            return false;
                        }
                        for (var i = 0; i < responseData.arrIdDeletePolygon.length; i++) {
                            var idPoly = responseData.arrIdDeletePolygon[ i ].id;
                            myMap.geoObjects.remove(polygons[ idPoly ]);
                            delete polygons[ idPoly ];
                        }
                        return true;
                    }
                    //---------------------------------------------------- 
                    document.body.onkeydown = function (event) {
                        var key = event.keyCode;
                        switch (key) {
                            case 38:
                                latitude = latitude + 0.00025;
                                break;
                            case 40:
                                latitude = latitude - 0.00025;
                                break;
                            case 39:
                                longitude = longitude + 0.00025;
                                break;
                            case 37:
                                longitude = longitude - 0.00025;
                                break;
                            default :
                                return false;
                        }
                        changePosition();
                    }
                    function modalInformWindow(stringInform) {
                          var modalInformWindow = document.createElement('div');
                          modalInformWindow.innerHTML = "<h1>" + stringInform + "</h1>";
                          document.body.insertBefore(modalInformWindow, document.body.firstChild);
                          modalInformWindow.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;background:#7A96A1;color:#FFFFF0;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;";
                          modalInformWindow.onclick = function () {
                             document.body.removeChild(modalInformWindow);
                           }
                    }
                    //============================================================================================================================================================
                    //Конструктор для ajax GET POST запросов.Возвращает обьект с методом this.setAjaxQuery.
                    //Вызов запроса: objectName.setAjaxQuery(theUrl,theParam,theFunct,theQuerType,theRespType) , где :
                    // theUrl      - url для запроса
                    // theParam    - строка параметров запроса ( "param1=value1&param2=value2" )
                    // theFunct    - ф-я которая будет обрабатывать результаты запроса
                    // theQuerType - тип запроса GET или POST
                    // theRespType - тип ответа : xml или text - какие данные получаем в ф-ю обработчик : responseXML или responseText
                    function AjaxGETResponse() {
                        var myReq = getXMLHttpRequest();
                        var cache = [];
                        var functionHandler;
                        var typeResponse;
                        var me = this;
                        if (myReq === false) {
                            dispMessageModalWindow("Fatal Error. ошибка создания обьекта XMLHttpRequest", '#D2691E');
                            return false;
                        } else {
                            myReq.upload.onerror = function () {
                                dispMessageModalWindow('Произошла ошибка при загрузке данных на сервер!', '#D2691E');
                            }
                            myReq.timeout = 30000;
                            myReq.ontimeout = function () {
                                dispMessageModalWindow('Извините, запрос превысил максимальное время', '#D2691E');
                            }
                        }
                        //--------------------------------------------------------------
                        this.send = function () {
                            sendAjaxQuery();
                        }

                        this.setAjaxQuery = function (theUrl, theParam, theFunct, theQuerType, theRespType) {
                            if (cache.length < 50) {
                                cache.push({'url': theUrl, 'param': theParam, 'funct': theFunct, 'typeQuery': theQuerType, 'typeResponse': theRespType});
                                sendAjaxQuery();
                            }
                        };
                        //======функция====отпр.===ajax===POST===запроса================
                        function sendAjaxQuery() {
                            if (!cache || cache.length == 0)
                                return false;
                            if (myReq) {
                                if (myReq.readyState == 4 || myReq.readyState == 0) {
                                    var cacheEntry = cache.shift();
                                    var theUrl = cacheEntry.url;
                                    var theParam = cacheEntry.param;
                                    var theTypeQuery = cacheEntry.typeQuery;
                                    functionHandler = cacheEntry.funct;
                                    typeResponse = cacheEntry.typeResponse;
                                    if (theTypeQuery == 'GET') {
                                        var strQuery = (theParam.length) ? theUrl + "?" + theParam : theUrl;
                                        myReq.open("GET", strQuery, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.send();
                                    } else if (theTypeQuery == 'POST') {
                                        myReq.open("POST", theUrl, true);
                                        myReq.onreadystatechange = getAjaxResponse;
                                        myReq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                        //					myReq.setRequestHeader("Content-length",theParam.length);
                                        //					myReq.setRequestHeader("Connection","close");
                                        myReq.send(theParam);
                                    }
                                } else {
                                    setTimeout(me.send, 1000);
                                }
                            } else {
                                dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
                            }
                            ;
                        }
                        ;
                        //-------------------------------------------------------------
                        function getAjaxResponse() {
                            if (myReq.readyState == 4) {
                                if (myReq.status == 200) {
                                    //				alert(myReq.responseText);
                                    if (typeResponse == 'xml') {
                                        var theXMLresponseDoc = myReq.responseXML;
                                        if (!myReq.responseXML || !myReq.responseXML.documentElement) {
                                            dispMessageModalWindow("Неверная структура документа XML .  " + myReq.responseText);
                                        } else {
                                            var firstNodeName = theXMLresponseDoc.childNodes[0].tagName;
                                            if (firstNodeName == 'response') {
                                                functionHandler(myReq.responseXML);
                                            } else if (firstNodeName == 'error') {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent, '#FFA500');
                                            } else {
                                                dispMessageModalWindow(theXMLresponseDoc.childNodes[0].textContent);
                                            }
                                        }
                                    } else {
                                        functionHandler(myReq.responseText);
                                    }
                                }
                            }
                        }
                        // ===== Ajax ===создание===обьекта====XMLHttpRequest===========
                        function getXMLHttpRequest()
                        {
                            var req;
                            if (window.ActiveXObject) {
                                try {
                                    req = new ActiveXObject("Microsoft.XMLHTTP");
                                } catch (e) {
                                    req = false;
                                }
                            } else {
                                try {
                                    req = new XMLHttpRequest();
                                } catch (e) {
                                    req = false;
                                }
                            }
                            return req;
                        }
                        ;
                        //----------------------------------------------------------------------------------------------------------------------------------------
                        function dispMessageModalWindow(messageData, colorData, domElementData) {
                            var div = document.createElement('div');
                            var color = colorData || '#7A96A1';
                            var domElement = domElementData || document.body;
                            div.style.cssText = "min-width:600px;max-width: 100%;min-height: 400px;max-height: 100%;cursor:pointer;padding:10px;color:black;font-sixe:1.3rem;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;background:" + color + ";";
                            domElement.insertBefore(div, domElement.firstChild);
                            div.innerHTML = messageData;
                            div.onclick = function () {
                                domElement.removeChild(div);
                            }
                            return true;
                        }
                    }
                    ;
                    //====================================================================================================================
                </script>	
            </BODY>
        </HTML>

        <?php
    }

}
