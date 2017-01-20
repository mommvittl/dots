<?php


namespace app\models;

use yii\base\Model;


class Signup extends Model
{
    public $username;
    public $email;
    public $password;


    public function rules()
    {
        return [
            [['username','email','password'],'required'],
            ['email','email'],
            ['email','unique','targetClass'=>'app\models\User'],
            ['password','string','min'=>2,'max'=>10]
        ];
    }

    public function signup()
    {
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->password = md5($this->password);
        $user->save();
    }

}