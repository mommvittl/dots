<?php

namespace app\models;
use yii\db\ActiveRecord;

class User_has_points extends ActiveRecord{
   
    public static function tableName() {
        return 'user_has_points' ;
    }
    
    
}
