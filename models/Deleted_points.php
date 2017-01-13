<?php


namespace app\models;
use yii\db\ActiveRecord;

class Deleted_points  extends ActiveRecord{
   public static function tableName() {
        return 'deleted_points' ;
    }
}
