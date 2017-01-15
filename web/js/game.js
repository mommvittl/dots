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
/*
 actionGetChange. Входные данные: сериализованный JSON - { 'lastDotId' : lastDotId, 'lastPolygonId' : lastPolygonId,
  'lastDelDotId' : lastDelDotId, 'lastDelPolygonId' : lastDelPolygonId }.
*/

function getData(){
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

function drawData(data) {
    data = JSON.parse(data);
    console.log(data);
}

function success(pos) {
    currentPos = pos.coords;
    myPoints[myPoints.length] = L.circle([currentPos.latitude, currentPos.longitude], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.5,
        radius: 10
    }).addTo(map);
    var time = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (time-start)/1000);
    start = performance.now();
}

function error(err) {
    console.warn('ERROR(' + err.code + '): ' + err.message);
}

setInterval(getData, 5000);