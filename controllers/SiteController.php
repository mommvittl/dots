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

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
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
    public function actions()
    {
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
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->runAction('site/login');
        }
        return $this->render('index');
    }
    /**
     * registration users in db
     *
     */
    public function actionSignup()
    {
        $model = new Signup();
        if (isset($_POST['Signup']))
        {
            $model->attributes = Yii::$app->request->post('Signup');
            if ($model->validate())
            {
                $model->signup();
                return $this->goHome();
            }
        }
        return$this->render('signup',['model'=>$model]);
    }
    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
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
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
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
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionRating()
    {
        $query = User::find()
            ->select(' `username`,`scores` ')
            ->where(' `scores` > 0 ')
            ->orderBy(' `scores` DESC  ')
            ->asArray()
            ->all();
        return $this->render('rating', ['scores' => $query]);
    }

     public function actionTesterhistory() {
         $query = Game::find()
                ->select( 'g.id as id, g.user1_id as idg1,  g.user2_id as idg2, u1.username as gm1, u2.username as gm2,`start_time`,'
                        . '`stop_time`, u3.username as wn,`user1_scores`,`user2_scores`' )
                ->from( '`game` as g , `user` as u1, `user` as u3, `user`as u2' )
                ->where( 'g.`user1_id` = u1.id AND g.`user2_id` = u2.id AND g.`winner_id` = u3.id' )
               ->asArray()
                ->all();
         return $this->render('testerhistory', ['games' => $query]);
    }

}
