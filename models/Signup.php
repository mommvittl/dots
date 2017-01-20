<?php


namespace app\models;

use Yii;

use yii\base\Model;


class Signup extends Model
{
    public $username;
    public $email;
    public $password;

    public $rememberMe = true;
    private $_user = false;


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
        return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
    }
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

}