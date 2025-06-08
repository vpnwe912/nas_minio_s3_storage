<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\LoginForm;

class SiteController extends Controller
{
    /** Ограничение доступа */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // перечисляем экшены, к которым применяем контроль
                'only' => ['index', 'logout'], 
                'rules' => [
                    // гостям разрешён только вход и страница ошибок
                    [
                        'actions' => ['login', 'error'],
                        'allow'   => true,
                    ],
                    // авторизованным — всё остальное (index, logout и т.д.)
                    [
                        'actions' => ['index', 'logout'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
        ];
    }

    /** Главная (после входа) */
    public function actionIndex()
    {
    //         $b = Yii::$app->minio->listBuckets();
    // var_dump($b);
    // exit;
        return $this->render('index');  // или редирект в админку
    }

    // Вход
    public function actionLogin()
    {
        $this->layout = 'main-login'; // Важно!
        if (!Yii::$app->user->isGuest) {
            // если уже в системе — на главную
            return $this->goHome();
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // после успешного входа — на предыдущую страницу или на index
            return $this->goBack();
        }
        $model->password = '';
        return $this->render('login', ['model' => $model]);
    }

    // Выход
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    // Обработка ошибок
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        return $this->render('error', ['exception' => $exception]);
    }
}
