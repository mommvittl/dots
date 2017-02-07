<?php

$this->title = 'Dots - rating';

?>

<div class="site-rating">
    <div class="body-content">
        <table class="table table-bordered table-striped">
            <tr>
                <th>Username</th>
                <th>Scores</th>
            </tr>
            <?php
            foreach ($scores as $line): ?>
                <tr>
                    <td><?=$line['username']?></td>
                    <td><?=$line['scores']?></td>
                </tr>
            <?php
            endforeach;
            ?>
        </table>
    </div>
</div>