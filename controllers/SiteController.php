<?php

namespace app\controllers;

use app\models\Signup;
use app\models\User;
use app\models\Game;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller {

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex() {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->runAction('site/login');
        }
        return $this->render('index');
    }

    /**
     * registration users in db
     *
     */
    public function actionSignup() {
        $model = new Signup();
        if (isset($_POST['Signup'])) {
            $model->attributes = Yii::$app->request->post('Signup');
            if ($model->validate()) {
                $model->signup();
                return $this->goHome();
            }
        }
        return$this->render('signup', ['model' => $model]);
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin() {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
                    'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout() {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact() {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');
            return $this->refresh();
        }
        return $this->render('contact', [
                    'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout() {
        return $this->render('about');
    }

    public function actionRating() {
        $query = User::find()
                ->select(' `username`,`scores` ')
                ->where(' `scores` > 0 ')
                ->orderBy(' `scores` DESC  ')
                ->asArray()
                ->all();
        return $this->render('rating', ['scores' => $query]);
    }

    public function actionHistory() {

        $query = Game::find()
                ->select('g.id as idGame, u1.username as user1_name,'
                        . ' u2.username as user2_name,`start_time`,'
                        . '`stop_time`, u3.username as winner_name,`user1_scores`,`user2_scores`')
                ->from('`game` as g , `user` as u1, `user` as u3, `user`as u2')
                ->where('g.`user1_id` = u1.id AND g.`user2_id` = u2.id AND g.`winner_id` = u3.id')
                ->orderBy('g.id DESC')
                ->limit(50)
                ->asArray()
                ->all();
         /*
             $query = Game::find()
                ->select(' g.`id` as idGame,g.`user1_scores`,g.`user2_scores`, u1.username as user1_name,'
                        . ' u2.username as user2_name,u3.username as winner_name,'
                        . ' g.`start_time` as start_time, g.`stop_time` as stop_time ')
                ->from('`game`as g , `user` as u1, `user` as u2, `user` as u3 ')
                ->where(' (g.`user1_id` = :idGamer OR g.`user2_id` = :idGamer) and u1.id = g.`user1_id`'
                        . ' and u2.id = g.`user2_id` and u3.id = g.`winner_id`')
                ->addParams([':idGamer' => $_SESSION[ '__id' ] ])
                ->orderBy('g.`id` DESC')
                ->asArray()
                ->all();
           return $this->render('testerhistory', ['games' => $query]);
         */
        return $this->render('history', ['games' => $query]);
    }

}
