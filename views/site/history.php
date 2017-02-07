<?php
$this->title = 'Dots - history';
// $this->registerJsFile('/web/js/history.js', ['position' => yii\web\View::POS_END]);

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
             <tr idGame="<?= $row['idGame'] ?>" startTime="<?= $row['start_time'] ?>"
                    stopTime="<?= $row['stop_time'] ?>" >
                <?php
                $color1 = ( $row['user1_name'] == $row['winner_name'] ) ? "winner" : "loser";
                $color2 = ( $row['user2_name'] == $row['winner_name'] ) ? "winner" : "loser";
                ?>
                    <td class="<?= $color1 ?>"><?= $row['user1_name'] ?></td>
                    <td class="<?= $color2 ?>"><?= $row['user2_name'] ?></td>
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

