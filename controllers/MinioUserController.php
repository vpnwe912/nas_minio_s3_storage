<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;

class MinioUserController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','update','delete','set-policy'],
                'rules'=>[
                    ['actions'=>['index','set-policy'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['create','update'],      'allow'=>true,'roles'=>['@']],
                    ['actions'=>['delete'],               'allow'=>true,'roles'=>['@']],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    // список MinIO-пользователей
    public function actionIndex()
    {
        $users = Yii::$app->minioAdmin->listUsers();
        return $this->render('index', ['users'=>$users]);
    }

    // форма создания
    public function actionCreate()
    {
        $m = new DynamicModel(['accessKey','secretKey']);
        $m->addRule(['accessKey','secretKey'],'required')
          ->addRule('accessKey','match',['pattern'=>'/^[A-Za-z0-9]+$/','message'=>'Только латинские буквы и цифры'])
          ->addRule('secretKey','string',['min'=>8]);

        if ($m->load(Yii::$app->request->post()) && $m->validate()) {
            if (Yii::$app->minioAdmin->createUser($m->accessKey, $m->secretKey)) {
                Yii::$app->session->setFlash('success','Пользователь создан');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error','Ошибка создания пользователя');
        }
        return $this->render('create',['model'=>$m]);
    }

    // удаление
    public function actionDelete(string $user)
    {
        if (Yii::$app->minioAdmin->deleteUser($user)) {
            Yii::$app->session->setFlash('success','Пользователь удалён');
        } else {
            Yii::$app->session->setFlash('error','Не удалось удалить пользователя');
        }
        return $this->redirect(['index']);
    }

    // назначить политику
    public function actionSetPolicy(string $user)
    {
        $policies = array_column(Yii::$app->minioAdmin->listPolicies(), 'policy');
        if ($post = Yii::$app->request->post('policy')) {
            if (Yii::$app->minioAdmin->setUserPolicy($user, $post)) {
                Yii::$app->session->setFlash('success','Политика назначена');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error','Ошибка назначения политики');
        }
        return $this->render('set-policy', [
            'user'     => $user,
            'policies' => $policies,
        ]);
    }

        /**
     * Смена секрета (пароля) пользователя
     */
    public function actionUpdate(string $user)
    {
        $model = new DynamicModel(['secretKey']);
        $model->addRule('secretKey','required')
              ->addRule('secretKey','string',['min'=>8]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // mc admin user add пересоздаёт секрет
            if (Yii::$app->minioAdmin->createUser($user, $model->secretKey)) {
                Yii::$app->session->setFlash('success', "Секрет для «{$user}» обновлён");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось обновить секрет");
            }
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'user'  => $user,
            'model' => $model,
        ]);
    }
}
