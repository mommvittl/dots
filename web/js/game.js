var point = null;
var points = [];
var currentPos = null;
var map = null;
var watchID = null;
var myDots = [];
var opponentDots = [];
var myPolygons = [];
var opponentPolygons = [];
var myMarker = null;
var lastDotId = 0;
var lastDot = null;
var lastPolygonId = 0;
var lastDelDotId = 0;
var lastDelPolygonId = 0;
var idGamer = 0;
var opponents = [];
var opponentId = 0;
var intervalId = null;
var myRadius = 0;

var options = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
};

var enemyMarker = L.icon({
    iconUrl: 'images/enemy-marker.png',
    iconSize: [25, 41],
    iconAnchor: [12.5, 41]
});

function test() {
    var testId = $('#testId').val();
    console.log(testId);
    $.ajax({
        type: 'POST',
        url: "/test/set-sessions",
        data: JSON.stringify({id: testId}),
        success: console.log('OK'),
        timeout: 4000
    });
}

var start = performance.now();
navigator.geolocation.getCurrentPosition(drawMap);

function drawMap(pos) {
    currentPos = pos.coords;
    if (!map) {
        $('#mapid').empty();
        map = L.map('mapid', {center: [currentPos.latitude, currentPos.longitude], zoom: 12});
        L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            maxZoom: 18,
            id: 'm1sha87.2hmg0n2n',
            accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
        }).addTo(map);
        myMarker = L.marker([currentPos.latitude, currentPos.longitude]).addTo(map);
        myRadius = L.circle([currentPos.latitude, currentPos.longitude], {
            color: 'blue',
            fillColor: 'blue',
            fillOpacity: 0.25,
            radius: currentPos.accuracy
        }).addTo(map);
        $('#ready').removeAttr('disabled');
    }
    var draw = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (draw-start)/1000);
    start = performance.now();
}

function getReady() {
    watchID = navigator.geolocation.watchPosition(setCurrentPos, error, options);
    console.log(watchID);
    sendPosition();
    intervalId = setInterval(sendPosition, 15000);
    $('#ready').attr('disabled', true);
}

function startGame() {
    stopWatch();
    watchID = navigator.geolocation.watchPosition(newPosition, error, options);
    console.log(watchID);
    clearInterval(intervalId);
    intervalId = setInterval(getData, 5000);
    $('#prepare').remove();
    $('#mapid').attr('class', 'col-sm-12');
    removeMarkers();
    map.remove();
    map = L.map('mapid', {center: [currentPos.latitude, currentPos.longitude], zoom: 12});
    L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        maxZoom: 18,
        id: 'm1sha87.2hmg0n2n',
        accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
    }).addTo(map);
}

function getData(data){
    console.log(data);
    if (watchID == null) {
        return false;
    }
    var lastIds = {lastDotId: lastDotId,
        lastPolygonId: lastPolygonId,
        lastDelDotId: lastDelDotId,
        lastDelPolygonId: lastDelPolygonId
        // idGamer: idGamer
    };
    console.log(JSON.stringify(lastIds));
    $.ajax({
        type: 'POST',
        url: "/round/get-change",
        data: JSON.stringify(lastIds),
        success: drawData,
        timeout: 4000
    });
}

function sendPoint(points){
    console.log(points);
    $.ajax({
        type: 'POST',
        url: "/round/change-position",
        data: JSON.stringify(points),
        success: getData,
        timeout: 4000
    });
}

/*{ 'opponent' : 0 , 'idGame' : 0 ,
 //   'arrOpponents' : [ { 'id' : id , 'nick' : nick , 'latitude' : latitude , 'longitude' : longitude } , ... ] } */

function drawOpponents(data) {
    console.log(data);
    if (data.status && data.status == "error") {
        return false;
    }
    if ((data.status && data.status == "ok") || (data.opponent && data.idGame)) {
        return startGame();
    } else {
        var arrOpponents = data.arrOpponents;
        var myPoint = L.point(currentPos.latitude, currentPos.longitude);
        removeMarkers();
        $('#players').empty();
        for (var j=0; j < arrOpponents.length; j++) {
            var enemyPoint = L.point(arrOpponents[j].latitude, arrOpponents[j].longitude);
            var distance = myPoint.distanceTo(enemyPoint);
            var text = arrOpponents[j].nick + " ( " + distance + " )";
            opponents[j] = L.marker([arrOpponents[j].latitude, arrOpponents[j].longitude], {icon: enemyMarker})
                .addTo(map).bindTooltip(arrOpponents[j].nick).openTooltip();
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
}

function selectedOpponent() {
    console.log("SELECTED");
    opponentId = $("#players option:selected").val();
    $('#enemySelect').removeAttr('disabled');
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
    console.log(point);
    $.ajax({
        type: 'POST',
        url: "/ruling/get-ready",
        data: JSON.stringify(point),
        success: drawOpponents,
        timeout: 9000
    });
}

function drawData(data) {
    if (data.status && data.status == "error") {
        return false;
    }

    if (data.arrAddDots.length > 0) {
        addDots(data.arrAddDots);
    }

    if (data.arrAddPolygon.length > 0) {
        addPolygons(data.arrAddPollygon);
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

function addDots(dots) {
    for (var i = 0; i < dots.length; i++) {
        if (dots[i].gamer = 'me') {
            myDots[dots[i].id] = L.circle([dots[i].latitude, dots[i].longitude], {
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.5,
                radius: 10
            }).addTo(map);
        } else {
            opponentDots[dots[i].id] = L.circle([dots[i].latitude, dots[i].longitude], {
                color: 'red',
                fillColor: 'red',
                fillOpacity: 0.5,
                radius: 10
            }).addTo(map);
        }
    }
    lastDotId = dots[dots.length-1].id;
}

function addPolygons(polygons) {
    for (var i=0; i < polygons.length; i++) {
        if (polygons[i].gamer = 'me') {
            myPolygons[polygons[i].id] = L.polygon(polygons[i].arrDots, {
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.5
            }).addTo(map);
        } else {
            opponentPolygons[polygons[i].id] = L.polygon(polygons[i].arrDots, {
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

function stopWatch() {
    console.log(watchID);
    navigator.geolocation.clearWatch(watchID);
    watchID = null;
    clearInterval(intervalId);
}

function setCurrentPos(pos) {
    currentPos = pos.coords;
}

function newPosition(pos) {
    currentPos = pos.coords;
    var time = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (time-start)/1000);
    start = performance.now();
    if (lastDot === null) {
        lastDot = currentPos;
        lastDot.accuracy = -1;
    }
    var currentPoint = L.point(currentPos.latitude, currentPos.longitude);
    var lastPoint = L.point(lastDot.latitude, lastDot.longitude);
    // var distance = currentPoint.distanceTo(lastPoint);
    var distance = distance(currentPoint, lastPoint);
    console.log(distance);
    // if (distance > lastPoint.accuracy) {
    if (true) {
        if (distance < 50) {
            point = {
                'latitude': currentPos.latitude,
                'longitude': currentPos.longitude,
                'accuracy': currentPos.accuracy,
                'speed': currentPos.speed
            };
            points = [point];
            sendPoint(points);
        } else {
            var count = parseInt(distance / 50);
            latitudeInc = (currentPos.latitude - lastDot.latitude) / count;
            longitudeInc = (currentPos.longitude - lastDot.longitude) / count;
            for (var i=1; i <= count; i++) {
                point = {
                    'latitude': lastDot.latitude + latitudeInc * i,
                    'longitude': lastDot.longitude + longitudeInc * i,
                    'accuracy': lastDot.accuracy,
                    'speed': lastDot.speed
                };
                points[points.length] = point;
            }
            sendPoint(points);
        }
    }
}

function error(err) {
    console.log('ERROR(' + err.code + '): ' + err.message);
}