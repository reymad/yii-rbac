<?php

namespace app\controllers;

use app\models\Post;
use Yii;
use app\models\ContactForm;
use app\models\LoginForm;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;

class SiteController extends MyController
{

    public function init()
    {
        parent::init();
    }

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
            /*
            'auth'  => [
                'class'           => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'authSuccess'],
            ],
            */
        ];
    }

    /**
     * This function will be triggered when user is successfuly authenticated using some oAuth client.
     *
     * @param \yii\authclient\ClientInterface $client
     * @return boolean|\yii\web\Response
     */

    /*
    public function authSuccess($client)
    {

        //$userAttributes = $client->getUserAttributes();

        switch (get_class($client)) {
            case Facebook::className():
                $clientName = 'facebook';
                break;
            case Twitter::className():
                $clientName = 'twitter';
                break;
            default:
                $clientName = false;
        }

        $response = Yii::$app->getResponse();
        $view = Yii::$app->getView();
        $viewData = [
            'userAttributes' => $userAttributes,
            'client'         => $clientName,
        ];
        $response->content = $view->renderFile(Yii::getAlias('@app/views/site/social-redirect.php'), $viewData);

        return $response;

    }
    */

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // siempre que inicialicemos cadenas así, el MyController las publicará en js
        $this->_translations['app.hola-mundo']  = Yii::t('app','Hola Mundo!!');
        // var_dump(Yii::$app->params['social_client']); exit;


        // var_dump(Yii::$app->user->identity->accounts['twitter']->decodedData); exit;

        // de momento solo he encontrado esta manera de acceder a la api de la social con la que nos hemos logado
        // funciona
        /*
         * Ok, si estoy logado en twitter, con esto accedo a la api
         * */
        /*
        if(isset(Yii::$app->user->identity->accounts['twitter'])){
            $client = Yii::$app->authClientCollection->getClient('twitter');
            // var_dump($client->api('statuses/home_timeline.json', 'GET'));
        }
        */
        /*
         * Ok, si estoy logado en fb, con esto accedo a algunos de los campos
         * */
        /*
        if(isset(Yii::$app->user->identity->accounts['facebook'])){
            $client = Yii::$app->authClientCollection->getClient('facebook');
            // var_dump($client->api('/me?fields=id,name,picture,about,birthday,cover,education', 'GET'));
        }
        */


        /* kartik extension */
        /*
        $social = Yii::$app->getModule('social');
        $social->facebook = [
            'appId' => 'YOUR_APP_ID',
            'secret' => 'YOUR_SECRET',
            // any other custom settings
        ];
        // fetch the facebook sdk api
        $facebook = $social->getFbApi();
        */

        if(
            Yii::$app->user->can('permisos_danielle')
            || Yii::$app->user->can('permisos_admin')// admin entra x aquí de momento
        ){

            // dani's provider
            $dataProvider = new ActiveDataProvider([
                'query' => Post::find()/*->joinWith('createdBy created_by')*/
                    ->where(['status' => Post::STATUS_ACTIVE])
                    ->orderBy([new \yii\db\Expression('FIELD (created_by, 8, 1) DESC, created_at desc')]),
                'pagination' => false,
            ]);
            return
                $this->render('timeline_dani', ['dataProvider'=>$dataProvider] );
        }
        else if(Yii::$app->user->can('permisos_usuario')){

            if(strtotime(date('Y-m-d H:i:s'))>=strtotime('2017-09-09 18:00:00')){

                // users can view public cards the timeline the day of her bd
                $dataProvider = new ActiveDataProvider([
                    'query' => Post::find()->where(['status' => Post::STATUS_ACTIVE, 'publico'=>1])->orderBy([new \yii\db\Expression('FIELD (created_by, 8, 1) DESC, created_at desc')]),
                    'pagination' => false,
                ]);
                return
                    $this->render('timeline_dani', ['dataProvider'=>$dataProvider] );

            }else{

                if(YII_ENV=='prod'){
                    $redirect = '/web/post/index';
                }else{
                    $redirect = '/post/index';
                }

                return
                    $this->redirect($redirect); //('timeline_user');
            }


        }else{// guest users

            $this->container=false;// no quiero container class en index

            $query = "select count(*) as total from " . Post::tableName() . " where status = " . Post::STATUS_ACTIVE;
            $this->totalCards = Yii::$app->db->createCommand($query)->queryScalar();

            return
                $this->render('index');
        }


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
}
