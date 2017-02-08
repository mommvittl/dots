<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => 'Dots',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index']],
            ['label' => 'Правила', 'url' => ['/site/about']],
            ['label' => 'Рейтинг', 'url' => ['/site/rating']],
            ['label' => 'История игр', 'url' => ['/site/history']],
            Yii::$app->user->isGuest ? (
                ['label' => 'Login', 'url' => ['/site/login']]
            ) : (
                '<li>'
                . Html::beginForm(['/site/logout'], 'post')
                . Html::submitButton(
                    'Logout (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'btn btn-link logout']
                )
                . Html::endForm()
                . '</li>'
            ),
/*
          Yii::$app->user->isGuest ? (
                 ['label' => 'tester', 'url' => ['/site/login']]
            ) : (
                 ['label' => 'tester', 'url' => ['/simulator/tester']]
            ),
*/
                 Yii::$app->user->isGuest ? (
                 ['label' => 'chat', 'url' => ['/site/login']]
            ) : (
                 ['label' => 'chat', 'url' => ['/chat/index']]
            )
        ],
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>

</div>
<?php /*
<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; Dots <?= date('Y') ?></p>
    </div>
</footer>
*/ ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
