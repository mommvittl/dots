var options = {
    enableHighAccuracy: true,
    timeout: 5000,
    maximumAge: 0
};

var currentPos = '';
var mymap = null;

function success(pos) {
    currentPos = pos.coords;
    if (!mymap) {
        mymap = L.map('mapid', {center: [currentPos.latitude, currentPos.longitude], zoom: 13});
        L.tileLayer('https://a.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            maxZoom: 18,
            id: 'm1sha87.2hmg0n2n',
            accessToken: 'pk.eyJ1IjoibTFzaGE4NyIsImEiOiJjaXhnOWg3N28wMDB6Mnp0bHd6eGZpZmFsIn0.51oROK3p2UywPFm3qIFYSQ'
        }).addTo(mymap);
        var marker = L.marker([currentPos.latitude, currentPos.longitude]).addTo(mymap);
    }
    console.log(currentPos.latitude + ', ' + currentPos.longitude + ', ' + currentPos.accuracy);
}

function error(err) {
    console.warn('ERROR(' + err.code + '): ' + err.message);
}

var nav = navigator.geolocation.watchPosition(success, error, options);
