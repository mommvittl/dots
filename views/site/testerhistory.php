<?php
$this->title = 'Dots - history';
$this->registerJsFile('https://api-maps.yandex.ru/2.1/?lang=ru_RU', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('/web/css/testerhistory.css');
$this->registerJsFile('/web/js/historyList.js', ['position' => yii\web\View::POS_END]);

?>
<div id="mapDiv">
    <div id="YMapsID" style="  height: 100vh; position: relative; margin: 0 auto;  "></div>
    <div id="controlViewHistoryDiv" class="controlViewHistoryDiv positionControlBut">
        <table id="controltable"><tr><td id="minBut">-</td><td id="playBut">►</td><td id="maxBut">+</td></tr></table>
        <div id="processing"></div>
    </div>
</div>
<div class="site-rating" id="tableContent">
    <div class="body-content">
        <table class="table table-bordered table-striped" id="historyTable">
            <tr>
                <th>User1</th>
                <th>User2</th>
                <th>Data</th>
                <th>User1 scores</th>
                <th>User2 scores</th>
            </tr>
<?php foreach ($games as $row): ?>
                <tr idGame="<?= $row['id'] ?>" startTime="<?= $row['start_time'] ?>"
                    stopTime="<?= $row['stop_time'] ?>"  idGamer1="<?= $row['idg1'] ?>" idGamer2="<?= $row['idg2'] ?>">
                <?php
                $color1 = ( $row['gm1'] == $row['wn'] ) ? "winner" : "loser";
                $color2 = ( $row['gm2'] == $row['wn'] ) ? "winner" : "loser";
                ?>
                    <td class="<?= $color1 ?>"><?= $row['gm1'] ?></td>
                    <td class="<?= $color2 ?>"><?= $row['gm2'] ?></td>
                    <td><?= $row['start_time'] ?></td>
                    <td><?= $row['user1_scores'] ?></td>
                    <td><?= $row['user2_scores'] ?></td>
                </tr>
    <?php
endforeach;
?>
        </table>
    </div>
</div>
