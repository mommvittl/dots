
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
var timerId;

var idEnemy;
var idGame;
var enemyNic;
var myScores = 0;
var enemyScores = 0;
var gameOverFlag = false;
var center = false;
// var timerId = setInterval(getNewCommand, 2000);
var time;
var idPosition = window.navigator.geolocation.watchPosition(successPosition, errorPosition, {enableHighAccuracy: true});
initializationVar();
functionNameForMyButClick = findGetReady;

var gameControl = document.getElementById('gameControl');
gameControl.onclick = function () {
    functionNameForMyButClick();
};
//-------------------------------------------------------------------------------------------------------------------------------------------------
ymaps.ready(function () {
    myMap = new ymaps.Map("YMapsID", {
        center: [49.94, 36.30],
        zoom: 18,
        //  controls: [ 'smallMapDefaultSet'  ]
      controls: [ "zoomControl" , "fullscreenControl"  ]
    });
    var myButton = new ymaps.control.Button( {
    data: {  content: "center"    },  
    options: {  size: "large"    }
 //   options: {   selectOnClick : false   }
     });
   // myButton.events.add( 'press',  function(){  myMap.setCenter([latitude, longitude]);  } );
   myButton.events.add( 'select',  function(){ center = true;  } ) ;
   myButton.events.add( 'deselect',  function(){ center = false;  } ) ;
    myMap.controls.add(myButton, { float: "left" });
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
    if( center ){   myMap.setCenter([latData, lonData]); }
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
    myScores = 0;
    enemyScores = 0;
    functionNameForMyButClick = emptyFunction;
}
//----------------------------------------------------
function changePosition() {
     time = performance.now();
    var parameter = [{'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed}];
    var theParam = JSON.stringify(parameter);
    ajaxPost.setAjaxQuery('/round/change-position', theParam, viewNewPosition, 'POST', 'text');
    moveMyPos(latitude, longitude);
}
function viewNewPosition(responseXMLDocument) {
    time = performance.now() - time;
    console.log('Время выполнения = ', time);
    //        alert(responseXMLDocument );
    console.log(responseXMLDocument);
    var response = JSON.parse(responseXMLDocument);
}
//------------------------------------------------------------------------------------------------------------------	
function successPosition(position) {
    newLatitude = position.coords.latitude;
    newLongitude = position.coords.longitude;
    newAccuracy = position.coords.accuracy;
    var dist = getDistanse(latitude, longitude, newLatitude, newLongitude);
    radiusAcc = (newAccuracy > 20) ? newAccuracy : 20;
    if (dist > radiusAcc) {
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
function getDistanse(lat, lon, newLat, newLon) {
    var x = Math.pow(newLat - lat, 2);
    var y = Math.pow(newLon - lon, 2);
    var d = Math.sqrt(x + y) / 0.0000075;
    return Math.round(d);
}
//-----------------------------------------------------------------------------------------------------------------------
function findGetReady() {
    functionNameForMyButClick = stopReadyCommand;
    getReadyCommand();
}
//------------------------------------------------------------------------------------------------------------------------
function getReadyCommand() {
    if (functionNameForMyButClick != stopReadyCommand) {
        return false;
    }
    document.getElementById('informStr').innerHTML = " Поиск игроков.";
    var theParam = JSON.stringify({'latitude': latitude, 'longitude': longitude, 'accuracy': accuracy, 'speed': speed});
    ajaxGet.setAjaxQuery('/ruling/get-ready', theParam, viewGetReady, 'POST', 'text');
}

function viewGetReady(responseXMLDocument) {
    // alert( responseXMLDocument );
    console.log(responseXMLDocument);
    var response = JSON.parse(responseXMLDocument);
    idEnemy = response.opponent;
    idGame = response.idGame;
    enemyNic = response.enemyNic
    var arrOpponents = response.arrOpponents;
    removeCollectionOpponents();
    if (idEnemy == 0 && idGame == 0) {
        var len = arrOpponents.length;
        document.getElementById('informStr').innerHTML = " Найдено  " + len + " игроков .";
        for (var i = 0; i < len; i++) {
            var dist = getDistanse(latitude, longitude, arrOpponents[ i ].latitude, arrOpponents[ i ].longitude);
            var gamerColor = (dist > 1000) ?'islands#grayStretchyIcon' : 'islands#darkGreenStretchyIcon';
    //        var gamerColor = (dist > 1000) ? 'islands#grayDotIconWithCaption' : 'islands#darkGreenDotIconWithCaption';
            var cont = arrOpponents[ i ].nick + " distance:  " + dist + " m.";
            var myPlacemark = new ymaps.Placemark([arrOpponents[ i ].latitude, arrOpponents[ i ].longitude], {iconContent: cont}, {preset: gamerColor});
    //        var myPlacemark = new ymaps.Placemark([arrOpponents[ i ].latitude, arrOpponents[ i ].longitude], {iconCaption: cont}, {preset: gamerColor});
            collectionOpponents[i] = myPlacemark;
            if (dist <= 1000) {
                collectionOpponents[i].events.add('click', selEnemyCommand.bind(arrOpponents[ i ]));
            }
            myMap.geoObjects.add(collectionOpponents[i]);
        }
        setTimeout(getReadyCommand, 10000);
    } else {
        removeCollectionOpponents();
        document.getElementById('informStr').innerHTML = "You: " + myScores + ", " + enemyNic + " " + enemyScores;
        var stringInform = " Старт игры <br> Ваш противник:  " + enemyNic;
        modalInformWindow(stringInform);
        functionNameForMyButClick = stopGame;
        timerId = setInterval(getNewCommand, 1000);
    }
}

function removeCollectionOpponents() {
    for (var i = 0; i < collectionOpponents.length; i++) {
        myMap.geoObjects.remove(collectionOpponents[i]);
    }
    collectionOpponents = [];
}
function redrawDots(){
    for( var dt in dots ){
         myMap.geoObjects.remove( dots[ dt ] );         
    }
    lastDotId = 0; 
    lastDelDotId = 0;
    dots = {};
}

// ---------------------------------------------------
function selEnemyCommand() {
    document.getElementById('informStr').innerHTML = " Инициализация игры... ";
    var theParam = JSON.stringify({'idEnemy': this.id});
    ajaxGet.setAjaxQuery('/ruling/enemy-selection', theParam, viewSelEnemy, 'POST', 'text');
}
function viewSelEnemy(responseXMLDocument) {
    //   alert(responseXMLDocument);
}
//----------------------------------------
function stopReadyCommand() {
    removeCollectionOpponents();
    document.getElementById('informStr').innerHTML = " Отмена статуса Ready... ";
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
    modal.style.cssText = "min-width: 80vw;max-width: 100%;min-height: 70vh;max-height: 100%;cursor:pointer;padding:10px;background:#BDBDBD;color:#3B3C1D;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;"
    var ok_but = document.getElementById('ok_but');
    var cancel_but = document.getElementById('cancel_but');
    ok_but.style.cssText = "border-radius:10px; padding: 30px; background:#FFE4E1; cursor:pointer; outline:none; margin-right: 20px;";
    cancel_but.style.cssText = "border-radius:10px; padding: 30px; background:#F5F5DC; cursor:pointer; outline:none; margin-left: 20px;";
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
    if (response.lastDelDotId) {
        lastDelDotId = response.lastDelDotId;
    }
    if (response.lastDelPolygonId) {
        lastDelPolygonId = response.lastDelPolygonId;
    }
    if (response.myScores) {
        myScores = response.myScores;
    }
    if (response.enemyScores) {
        enemyScores = response.enemyScores;
    }
    document.getElementById('informStr').innerHTML = "You: " + myScores + " " + enemyNic + " " + enemyScores;
}
function  responseStatusGameOver(response) {
    var statusGame = response.message;
    var stringInform = "Окончание игры. <br> Победитель " + statusGame.winner + "<br> Ваши очки: " + statusGame.scoresMe + "<br> очки соперника: " + statusGame.scoresEnemy;
    clearInterval(timerId);
    gameOverFlag = true ;
    modalInformWindow(stringInform);
    removeGame();
}
function  responseStatusError(response) {
    //  alert('Error: ' + response.message)
}
function removeGame() {
    for (element in dots) {
        myMap.geoObjects.remove(dots[ element ]);
    }
    for (element in polygons) {
        myMap.geoObjects.remove(polygons[ element ]);
    }
    initializationVar();
    functionNameForMyButClick = findGetReady;
    document.getElementById('informStr').innerHTML = " Нажмите start для поиска игроков ";
    var theParam = JSON.stringify({});
    ajaxGet.setAjaxQuery('/ruling/remove-session', theParam, emptyFunction, 'POST', 'text');
}
//------------------------------------------------------
function  viewAddDots(responseData) {
    if (!responseData.arrAddDots || responseData.arrAddDots.length == 0) {
        return false;
    }
    for (var i = 0; i < responseData.arrAddDots.length; i++) {
        if( !(responseData.arrAddDots[ i ].id in dots) ){
        var gamerColor = (responseData.arrAddDots[ i ].gamer == 'me') ? "#F4A46077" : "#87CEFA77";
        var accuracy = (responseData.arrAddDots[ i ].accuracy < 33) ? responseData.arrAddDots[ i ].accuracy : 33;
        lastDotId = responseData.arrAddDots[ i ].id;
        dots[ lastDotId ] = new ymaps.Circle(
                [[responseData.arrAddDots[ i ].latitude, responseData.arrAddDots[ i ].longitude], accuracy],
                {},
                {fillColor: gamerColor, strokeOpacity: 0.8, strokeWidth: 1}
        );
        myMap.geoObjects.add(dots[ lastDotId ]);
       }
    }
    return true;
}
function  viewAddPolygons(responseData) {
    if (!responseData.arrAddPolygon || responseData.arrAddPolygon.length == 0) {
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
                [poligon],
                {hintContent: "Многоугольник"},
                {fillColor: gamerColor, strokeWidth: 8, opacity: 0.5}
        );
        myMap.geoObjects.add(polygons[ lastPolygonId ]);
    }
    redrawDots();
}
function  viewDeleteDots(responseData) {
    if (!responseData.arrIdDeleteDots || responseData.arrIdDeleteDots.length == 0) {
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
    if (!responseData.arrIdDeletePolygon || responseData.arrIdDeletePolygon.length == 0) {
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
    modalInformWindow.style.cssText = "min-width:  80vw; max-width: 100%;min-height: 70vh; max-height: 100%;cursor:pointer;padding:10px;background:#7A96A1;color:#FFFFF0;text-align:center;font: 1em/2em arial;border: 4px double #1E0D69;position:fixed;z-index: 1000;top:50%;left:50%;transform:translate(-50%, -50%);box-shadow: 6px 6px #14536B;";
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
                    // myReq.setRequestHeader("Content-length",theParam.length);
                    // myReq.setRequestHeader("Connection","close");
                    myReq.send(theParam);
                }
            } else {
                setTimeout(me.send, 1000);
            }
        } else {
            dispMessageModalWindow("Error - ошибка 2 создания обьекта XMLHttpRequest");
        }
    }
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

