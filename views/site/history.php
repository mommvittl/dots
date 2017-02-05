<?php
$this->title = 'Dots - history';
$this->registerJsFile('/web/js/history.js', ['position' => yii\web\View::POS_END]);

?>
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
                $color1 = ( $row['gm1'] == $row['wn'] ) ? "red" : "blue";
                $color2 = ( $row['gm2'] == $row['wn'] ) ? "red" : "blue";
                ?>
                    <td style=" color: <?= $color1 ?> ;"><?= $row['gm1'] ?></td>
                    <td style=" color: <?= $color2 ?> ;"><?= $row['gm2'] ?></td>
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

