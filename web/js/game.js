var simulation = false;
var point = null;
var points = [];
var currentPos = null;
var currentPoint = null;
var map = null;
var watchID = null;
var myDots = [];
var opponentDots = [];
var myPolygons = [];
var opponentPolygons = [];
var myMarker = null;
var lastDotId = 0;
var lastDot = null;
var lastPoint = null;
var lastPolygonId = 0;
var lastDelDotId = 0;
var lastDelPolygonId = 0;
var idGamer = 0;
var opponents = [];
var opponentId = 0;
var intervalId = null;
var myRadius = 0;
var ready = false;
var simulateInterval = null;
var enemyMarker = null;
var icon = null;
var requesting = false;
var options = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
};
var enemyIcon = L.icon({
    iconUrl: '/images/enemy-marker.png',
    iconSize: [25, 41],
    iconAnchor: [12.5, 41]
});
var greyIcon = L.icon({
    iconUrl: '/images/grey-marker.png',
    iconSize: [25, 41],
    iconAnchor: [12.5, 41]
});

// Задает вспомогательный текст для пользователя
function changeHelpText(text) {
    $('#help').text(text);
}

// Определяет текущую позицию
function startGPS() {
    modeSelected();
    navigator.geolocation.getCurrentPosition(drawMap, errorCurrent);
    changeHelpText('Getting your position...');
}

// Если позиция не определена, перезапускается определение текущей позиции
function errorCurrent() {
    startGPS();
}

// Включение режима симуляции. Отрисовка карты с заданной позицией (центр Харькова)
function startSimulation() {
    modeSelected();
    simulation = true;
    drawMap({ latitude: 49.98986319656137, longitude: 36.229476928710945, accuracy: 40, speed: 0});
    watchID = 0;
    map.on('click', onMapClick);
    changeHelpText('Click on map');
    bindKeys();
}

// Смена декораций
function modeSelected() {
    $('#mode').attr('hidden', 'true');
    $('#game').removeAttr('hidden');
}

// Отрисовка карты и маркера игрока
function drawMap(pos) {
    var center = pos;
    if (!simulation) {
        center = pos.coords;
        currentPos = pos.coords;
    }
    if (!map) {
        map = L.map('mapid', {center: [center.latitude, center.longitude], zoom: 12});
        L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            maxZoom: 18,
            id: 'm1sha87.2hmg0n2n',
            accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
        }).addTo(map);
        if (!simulation) {
            myMarker = L.marker([center.latitude, center.longitude]).addTo(map);
            myRadius = L.circle([center.latitude, center.longitude], {
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.25,
                radius: center.accuracy
            }).addTo(map);
            getReady();
        }
    }
}

// Определяет текущую позицию и добавляет маркер на карту
function onMapClick(e) {
    if (!myMarker) {
        myMarker = L.marker(e.latlng).addTo(map);
    }
    myMarker = setLatLng(e.latlng);
    currentPos = {latitude: e.latlng.lat, longitude: e.latlng.lng, accuracy: 40, speed: 0};
    getReady();
}

// Запускает слежение и активирует интервал обновления позиции
function getReady() {
    if (!simulation) {
        watchID = navigator.geolocation.watchPosition(setCurrentPos, errorNavigate, options);
    }
    ready = true;
    sendPosition();
    intervalId = setInterval(sendPosition, 15000);
}

function errorNavigate() {

}

// Запуск игры
function startGame() {
    clearInterval(intervalId);
    if (!simulation) {
        changeHelpText(' ');
        stopWatch();
        watchID = navigator.geolocation.watchPosition(newPosition, errorNavigate, options);
        intervalId = setInterval(getData, 5000);
    } else {
        $('#help').text('Press A,S,W,D to move..');
        simulateInterval = setInterval(getData, 5000);
    }
    $('#prepare').remove();
    $('#mapid').attr('class', 'col-sm-12');
    removeMarkers();
    map.remove();
    map = L.map('mapid', {center: [currentPos.latitude, currentPos.longitude], zoom: 14});
    L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        maxZoom: 18,
        id: 'm1sha87.2hmg0n2n',
        accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
    }).addTo(map);
    $('#gameover').removeAttr('hidden');
    getData();
}

// Остановка игры
function stopGame() {
    $.ajax({
        type: 'POST',
        url: "/ruling/stop-game",
        success: getData,
        timeout: 4000
    });
}

function getData(data){
    if (watchID == null) {
        return false;
    }
    if (typeof(data) !== 'undefined') {
        requesting = false;
    }
    if (!requesting) {
        var lastIds = {
            lastDotId: lastDotId,
            lastPolygonId: lastPolygonId,
            lastDelDotId: lastDelDotId,
            lastDelPolygonId: lastDelPolygonId
        };
        $.ajax({
            type: 'POST',
            url: "/round/get-change",
            data: JSON.stringify(lastIds),
            success: drawData,
            error: error,
            timeout: 4000
        });
        requesting = true;
    }
}

function sendPoint(points){
    if (!requesting) {
        $.ajax({
            type: 'POST',
            url: "/round/change-position",
            data: JSON.stringify(points),
            success: getData,
            error: error,
            timeout: 20000
        });
        requesting = true;
    }
}

function error() {
    requesting = false;
}

function drawOpponents(data) {
    if (data.status && data.status == "error") {
        return false;
    }
    if ((data.status && data.status == "ok") || (data.opponent && data.idGame)) {
        startGame();
        return true;
    } else {
        changeHelpText('Choose your opponent...');
        var arrOpponents = data.arrOpponents;
        var myPoint = L.latLng(currentPos.latitude, currentPos.longitude);
        removeMarkers();
        $('#players').empty();
        for (var j=0; j < arrOpponents.length; j++) {
            var enemyPoint = L.latLng(arrOpponents[j].latitude, arrOpponents[j].longitude);
            var distance = parseInt(myPoint.distanceTo(enemyPoint));
            if (distance > 5000) {
                icon = greyIcon;
            } else {
                icon = enemyIcon;
            }
            var text = arrOpponents[j].nick + " ( " + distance + " m )";
            opponents[j] = L.marker([arrOpponents[j].latitude, arrOpponents[j].longitude],
                {icon: icon, id: arrOpponents[j].id})
                .addTo(map).bindTooltip(arrOpponents[j].nick).openTooltip();
            opponents[j].on('click', selectedOpponent);
            $('#players').append($('<option>', {
                value: arrOpponents[j].id,
                text: text
            }));
        }
        myMarker = L.marker([currentPos.latitude, currentPos.longitude]).addTo(map);
        myRadius = L.circle([currentPos.latitude, currentPos.longitude], {
            color: 'blue',
            fillColor: 'blue',
            fillOpacity: 0.25,
            radius: currentPos.accuracy
        }).addTo(map);
    }
}

function removeMarkers() {
    for (var i=0; i < opponents.length; i++) {
        map.removeLayer(opponents[i]);
    }
    map.removeLayer(myMarker);
    map.removeLayer(myRadius);
    myMarker = null;
}
function selectedOpponent() {
    if (this.options.id) {
        opponentId = this.options.id;
        return enemySelect();
    }
    opponentId = $("#players option:selected").val();
    return enemySelect();
}

function enemySelect() {
    if (opponentId) {
        var idEnemy = {idEnemy : opponentId};
        $.ajax({
            type: 'POST',
            url: "/ruling/enemy-selection",
            data: JSON.stringify(idEnemy),
            success: drawOpponents,
            timeout: 4000
        });
    }
}


function sendPosition(){
    var point = {
        'latitude': currentPos.latitude,
        'longitude': currentPos.longitude,
        'accuracy': currentPos.accuracy,
        'speed': currentPos.speed
    };
    $.ajax({
        type: 'POST',
        url: "/ruling/get-ready",
        data: JSON.stringify(point),
        success: drawOpponents,
        timeout: 9000
    });
}

function drawData(data) {
    requesting = false;
    points = [];
    $('#error').empty().attr('hidden');
    if (data.status && data.status == "error") {
        $('#error').removeAttr('hidden').text(data.message);
        return false;
    }

    if (data.status && data.status == "gameOver") {
        finalScores(data.message);
        return false;
    }

    if (data.arrAddDots.length > 0) {
        addDots(data.arrAddDots);
    }

    if (data.arrAddPolygon.length > 0) {
        addPolygons(data.arrAddPolygon);
    }

    if (data.arrIdDeleteDots.length > 0) {
        deleteDots(data.arrIdDeleteDots);
        lastDelDotId = data.lastDelDotId;
    }

    if (data.arrIdDeletePolygon.length > 0) {
        deletePolygons(data.arrIdDeletePolygon);
        lastDelPolygonId = data.lastDelPolygonId;
    }
}

function finalScores(data) {
    stopWatch();
    var text = (data.winner == 'me') ? 'YOU WIN!' : 'YOU LOSE!';
    $('#winner').text(text);
    $('#scoresMe').text('Your scores: ' + data.scoresMe);
    $('#scoresEnemy').text('Opponent scores: ' + data.scoresEnemy);
    $('#finalScores').modal('show');
}

function restart() {
    location.reload();
}

function addDots(dots) {
    for (var i = 0; i < dots.length; i++) {
        if (dots[i].gamer == 'me') {
            myDots[dots[i].id] = L.circle([dots[i].latitude, dots[i].longitude], {
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.5,
                radius: dots[i].accuracy
            }).addTo(map);
            lastDot = {latitude: dots[i].latitude, longitude: dots[i].longitude, accuracy: dots[i].accuracy};
        } else {
            opponentDots[dots[i].id] = L.circle([dots[i].latitude, dots[i].longitude], {
                color: 'red',
                fillColor: 'red',
                fillOpacity: 0.5,
                radius: dots[i].accuracy
            }).addTo(map);
            if (!enemyMarker) {
                enemyMarker = L.marker([dots[i].latitude, dots[i].longitude],
                    {icon: enemyIcon})
                    .addTo(map)
            } else {
                enemyMarker.setLatLng([dots[i].latitude, dots[i].longitude]);
            }
        }
    }
    if (simulation && !currentPoint && lastDot) {
        currentPos = {latitude: lastDot.latitude, longitude: lastDot.longitude, accuracy: lastDot.accuracy, speed: lastDot.speed} ;
    }
    lastDotId = dots[dots.length-1].id;
}

function addPolygons(polygons) {
    for (var i=0; i < polygons.length; i++) {
        if (polygons[i].gamer == 'me') {

            myPolygons[polygons[i].id] = L.polygon(polygons[i].arrDot, {
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.5
            }).addTo(map);
        } else {
            opponentPolygons[polygons[i].id] = L.polygon(polygons[i].arrDot, {
                color: 'red',
                fillColor: 'red',
                fillOpacity: 0.5
            }).addTo(map);
        }
    }
    lastPolygonId = polygons[polygons.length-1].id;
}

function deleteDots(dots) {
    for (var i=0; i < dots.length; i++) {
        var id = dots[i].id;
        if (myDots[id]) {
            map.removeLayer(myDots[id]);
        }
        if (opponentDots[id]) {
            map.removeLayer(opponentDots[id]);
        }
    }
}

function deletePolygons(polygons) {
    for (var i=0; i < polygons.length; i++) {
        var id = polygons[i].id;
        if (myPolygons[id]) {
            map.removeLayer(myPolygons[id]);
        }
        if (opponentPolygons[id]) {
            map.removeLayer(opponentPolygons[id]);
        }
    }
}

function bindKeys() {
    $("html").keydown(function(event){
        var key = event.keyCode;
        switch (key){
            case 87:
            case 119:
                currentPos.latitude = parseFloat(currentPos.latitude) + 0.0005;
                break;
            case 83:
            case 115:
                currentPos.latitude = parseFloat(currentPos.latitude) - 0.0005;
                break;
            case 68:
            case 100:
                currentPos.longitude = parseFloat(currentPos.longitude) + 0.0005;
                break;
            case 65:
            case 97:
                currentPos.longitude = parseFloat(currentPos.longitude) - 0.0005;
                break;
        }
        newPosition();
    });
}

function stopWatch() {
    navigator.geolocation.clearWatch(watchID);
    watchID = null;
    clearInterval(intervalId);
    clearInterval(simulateInterval);
}

function setCurrentPos(pos) {
    currentPos = pos.coords;
}
function newPosition(pos) {
    if (!simulation) {
        currentPos = pos.coords;
    }
    if (!lastDot) {
        lastDot = {latitude: currentPos.latitude, longitude: currentPos.longitude, accuracy: currentPos.accuracy, speed: currentPos.speed};
        lastDot.accuracy = -1;
    }
    if (simulation) {
        currentPos.accuracy = parseInt((Math.random() * 30) + 10);
    }
    currentPoint = L.latLng(currentPos.latitude, currentPos.longitude);
    lastPoint = L.latLng(lastDot.latitude, lastDot.longitude);
    if (!myMarker) {
        myMarker = L.marker([currentPos.latitude, currentPos.longitude]).addTo(map);
    } else {
        myMarker.setLatLng([currentPos.latitude, currentPos.longitude]);
    }
    var distance = currentPoint.distanceTo(lastPoint);
    if (distance >= 20) {
        point = {
            'latitude': currentPos.latitude,
            'longitude': currentPos.longitude,
            'accuracy': currentPos.accuracy,
            'speed': currentPos.speed
        };
        points = [point];
        sendPoint(points);
    }
}
