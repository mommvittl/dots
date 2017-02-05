var ajaxGet = new AjaxGETResponse;
var mapDiv = document.getElementById('mapDiv');
var tableContent = document.getElementById('tableContent');
var historyTable = document.getElementById('historyTable');
var controlViewHistoryDiv = document.getElementById('controlViewHistoryDiv');
var minBut = document.getElementById('minBut');
var playBut = document.getElementById('playBut');
var maxBut = document.getElementById('maxBut');
var processing = document.getElementById('processing');
minBut.onclick = minButFun;
playBut.onclick = playButFun;
maxBut.onclick = maxButFun;
processing.onclick = processingFun;
mapDiv.hidden = true;
var idGame;
var startTime;
var stopTime;
var idGamer1;
var idGamer2;
var myMap;
var dots = {};
var polygons = {};
var oldTime = 0;
var newTime = 0;
var interval = 0;
var timeOut = 1;

historyTable.onclick = function (event) {
    var target = event.target;
    if (target.tagName == 'TD') {
        var row = target.parentElement;
        idGame = row.getAttribute('idGame');
        startTime = row.getAttribute('startTime');
        stopTime = row.getAttribute('stopTime');
        idGamer1 = row.getAttribute('idGamer1');
        idGamer2 = row.getAttribute('idGamer2');
        tableContent.hidden = true;
        mapDiv.hidden = "";
        mainFunction();
    }
};

ymaps.ready(function () {
    myMap = new ymaps.Map("YMapsID", {
        center: [49.94, 36.30],
        zoom: 18,
        controls: ["zoomControl", "fullscreenControl"]
    });
});

function mainFunction() {
  oldTime = startTime;
 newTime = stopTime;
    getNewCommand();
}

function minButFun() {
    if (interval >= 100) {
        interval = interval - 100;
    }
    alert(interval);
}
function playButFun() {
    alert('sfdgafg');
}
function maxButFun() {
    if (interval <= 10000) {
        interval = interval + 100;
    }
    alert(interval);
}
function processingFun() {
    alert('sfdgafg');
}
//-------------------------------------------------------------------------------------------------------------------------------
function getNewCommand() {
    var theParam = JSON.stringify({ 'idGame': idGame, 'idGamer': idGamer1, 'idEnemy': idGamer2,  'oldTime': oldTime, 'newTime': newTime });
    ajaxGet.setAjaxQuery('/history/history', theParam, getResponseScript, 'POST', 'text');
}
function getResponseScript(responseXMLDocument) {
    alert(responseXMLDocument);
       return;
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
}
function  responseStatusGameOver(response) {
    //  alert('Error: ' + response.message)
}
function  responseStatusError(response) {
    //  alert('Error: ' + response.message)
}
//------------------------------------------------------
function  viewAddDots(responseData) {
    if (!responseData.arrAddDots || responseData.arrAddDots.length == 0) {
        return false;
    }
    for (var i = 0; i < responseData.arrAddDots.length; i++) {
        if (!(responseData.arrAddDots[ i ].id in dots)) {
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