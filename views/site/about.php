<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'About';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        This is the About page. You may modify the following file to customize its content:
    </p>

    <code><?= __FILE__ ?></code>
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