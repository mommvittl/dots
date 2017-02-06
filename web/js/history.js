var tableContent = document.getElementById('tableContent');
var historyTable = document.getElementById('historyTable');
var idGame;
var startTime;
var stopTime;
var idGamer1;
var idGamer2;
historyTable.onclick = function (event) {
    var target = event.target;
    if (target.tagName == 'TD') {
        var row = target.parentElement;
        idGame = row.getAttribute('idGame');
        startTime = row.getAttribute('startTime');
        stopTime = row.getAttribute('stopTime');
        idGamer1 = row.getAttribute('idGamer1');
        idGamer2 = row.getAttribute('idGamer2');
        mainFunction();
    }
};
function  mainFunction(){
 
      var theParam = JSON.stringify({ 'idGame': idGame, 'startTime': '2017-02-02 21:29:30', 'stopTime': '2017-02-02 21:29:52' });
    $.ajax({   type: 'POST', url: "/history/index?au=35",  data:  theParam ,   success: getResponseScript,    timeout: 9000  });
       alert( 'idGame: ' + idGame +  ' startTime: ' + startTime + ' stopTime: ' + stopTime );
}
//================================================================================
function getResponseScript(response) {
    alert( JSON.stringify(response ) );   
   return;
 
}