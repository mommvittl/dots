var currentPos = null;
var map = null;
var watchID = null;
var myPoints = [];
var myMarker = null;
var lastDotId = 0;
var lastPolygonId = 1;
var lastDelDotId = 0;
var lastDelPolygonId = 0;
var idGamer = 0;

var options = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
};

var start = performance.now();
navigator.geolocation.getCurrentPosition(drawMap);

function drawMap(pos) {
    currentPos = pos.coords;
    if (!map) {
        $('#mapid').text('');
        map = L.map('mapid', {center: [currentPos.latitude, currentPos.longitude], zoom: 13});
        L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            maxZoom: 18,
            id: 'm1sha87.2hmg0n2n',
            accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
        }).addTo(map);
        myMarker = L.marker([currentPos.latitude, currentPos.longitude]).addTo(map);
        L.circle([currentPos.latitude, currentPos.longitude], {
            color: 'blue',
            fillColor: 'blue',
            fillOpacity: 0.25,
            radius: currentPos.accuracy
        }).addTo(map);
    }
    var draw = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (draw-start)/1000);
    start = performance.now();
    watchID = navigator.geolocation.watchPosition(success, error, options);
}

function getData(){
    if (watchID == null) {
        return false;
    }
    var data = {lastDotId: lastDotId,
        lastPolygonId: lastPolygonId,
        lastDelDotId: lastDelDotId,
        lastDelPolygonId: lastDelPolygonId,
        idGamer: idGamer
    };
    console.log(JSON.stringify(data));
    $.ajax({
        type: 'POST',
        url: "/round/get_change",
        data: JSON.stringify(data),
        success: drawData,
        timeout: 4000
    });
}

function sendPoint(point){
    console.log(JSON.stringify(point));
    console.log(point);
    $.ajax({
        type: 'POST',
        url: "/round/change_position",
        data: JSON.stringify(point),
        success: getData,
        timeout: 4000
    });
}
/*
Object {
    arrAddDots: Array[0],
    arrAddPolygon: Array[0],
    arrIdDeleteDots: Array[0],
    arrIdDeletePolygon: Array[0],
    lastDelDotId: 0,
    lastDelPolygonId: 0 }
*/

function drawData(data) {
    data = JSON.parse(data);
    console.log(data);
    /*myPoints[myPoints.length] = L.circle([currentPos.latitude, currentPos.longitude], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.5,
        radius: 10
    }).addTo(map);*/
}

function stopWatch() {
    navigator.geolocation.clearWatch(watchID);
    watchID = null;
}

function success(pos) {
    currentPos = pos.coords;
    var time = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (time-start)/1000);
    start = performance.now();
    var point = {'latitude': currentPos.latitude,
        'longitude': currentPos.longitude,
        'accuracy': currentPos.accuracy,
        'speed': currentPos.speed
    };
    sendPoint(point);
}

function error(err) {
    console.warn('ERROR(' + err.code + '): ' + err.message);
}

setInterval(getData, 5000);