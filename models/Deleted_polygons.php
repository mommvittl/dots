<?php


namespace app\models;
use yii\db\ActiveRecord;

class Deleted_polygons  extends ActiveRecord{
    public static function tableName() {
        return 'deleted_polygons' ;
    }
}
