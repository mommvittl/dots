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
        myPoint = L.circle([currentPos.latitude, currentPos.longitude], {
            color: 'blue',
            fillColor: 'blue',
            fillOpacity: 0.25,
            radius: currentPos.accuracy
        }).addTo(map);
    }
    var draw = performance.now();
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy + " " + (draw-start)/1000);
    start = performance.now();
    watchID = navigator.geolocation.watchPosition(newPosition, error, options);
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
        url: "/round/get-change",
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
        url: "/round/change-position",
        data: JSON.stringify(point),
        success: getData,
        timeout: 4000
    });
}

function drawData(data) {
    console.log(data);

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
    navigator.geolocation.clearWatch(watchID);
    watchID = null;
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
    var distance = currentPoint.distanceTo(lastPoint);
    console.log(distance);
    if (distance > lastPoint.accuracy) {
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
    console.warn('ERROR(' + err.code + '): ' + err.message);
}

setInterval(getData, 5000);