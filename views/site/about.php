<?php
/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'rules ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">

    <p>
    <h1 style="text-align: center;">Правила игры DOTS</h1>
    <h3>Приветствуем тебя в новой , увлекательной экшн-игре &quot;Dots&quot;!!!</h3>
    <p><font size="5">Цель игры:</font>
        передвигаясь по городу захватить как можно больше территории.
        Ваше положение и движение отображаеться в режиме реального времени точками на экране
        (так же вы можете видеть точки противника). За каждую созданую точку начисляются очки.
        Для захвата территории необходимо обойти некоторую часть местности&nbsp;
        и вернуться на прежднее место где вы уже проходили до этого. В результате
        на экране будет отображен ваш полигон ( замкнутая территория ).
        Если в этот полигон попадают точки или полигоны противника - они удаляются ,
        а вы получаете бонусные очки.
        Если вы сумели таким образом окружить полигоном самого противника - вы победили.
        Если вы или противник заходит в уже созданый полигон - то все точки теряюся, но сама
        игра продолжается и на выходе из полигона точки будут создаваться вновь.
        Игру можно завершить досрочно - сдавшись. Продолжительность игры - 60 мин. , по истечении
        которых победитель определяется по очкам.</p>
    <p>&nbsp;</p>
    <ul>
        <li><font size="4">Зарегестрироваться на сайте</font></li>
        <li><font size="4">Войти под своим логином</font></li>
        <li><font size="4">Включить GPS модуль</font></li>
        <li><font size="4">нажать кнопку &quot;ready&quot; и выбрать противника</font></li>
        <li><font size="4">нажать &quot;start&quot;</font></li>
        <li><font size="4">передвигаться по территории</font></li>
    </ul>

</p>


</div>
<script>
    var options = {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
    };

    function success(pos) {
        var crd = pos.coords;

        console.log('Your current position is:');
        console.log('Latitude : ' + crd.latitude);
        console.log('Longitude: ' + crd.longitude);
        console.log('More or less ' + crd.accuracy + ' meters.');
    }

//    navigator.geolocation.clearWatch(id);

    function error(err) {
        console.warn('ERROR(' + err.code + '): ' + err.message);
    }

    id = navigator.geolocation.watchPosition(success, error, options);
</script>