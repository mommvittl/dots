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
    <p><font size="5">Цель игры:</font> передвигаясь по улицам и териториям своего города используя смартфон, или любое другое устройство образовывать полигоны. Полигон считается вашим если вы обошли некоторую часть местности&nbsp; и вернулись на прежднее место где вы уже проходили до этого, при этом не пересекая занятых полигонов противника. Ваше положение и движение отображаеться в режиме реального времени(так же вы можете видеть положение и передвижение противника). Основная цель захватить как можно больше полигонов противника за отведенное время. Игра считается законченой по истечению времени. Выигрывает тот игрок, который набрал наибольшее колличество захваченых точек полигона&nbsp; противника. Игра может быть досрочно закончена если текущее положение игрока окажется в только-что образованном полигоне противника.</p>
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