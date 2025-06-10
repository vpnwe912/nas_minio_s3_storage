<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;
use app\models\MinioAccounts;

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

    // форма создания пользователя MinIO
    public function actionCreate()
    {
        $m = new \yii\base\DynamicModel(['accessKey','secretKey']);
        $m->addRule(['accessKey','secretKey'],'required')
          ->addRule('accessKey','match',['pattern'=>'/^[A-Za-z0-9]+$/','message'=>'Только латинские буквы и цифры'])
          ->addRule('secretKey','string',['min'=>8]);
    
        // Загружаем данные формы и валидируем
        if ($m->load(Yii::$app->request->post()) && $m->validate()) {
    
            // Пробуем создать пользователя в MinIO
            if (Yii::$app->minioAdmin->createUser($m->accessKey, $m->secretKey)) {
    
                // --- Логика создания пользователя сайта ---
                $user = \app\models\User::findOne(['username' => $m->accessKey]);
                if (!$user) {
                    $user = new \app\models\User();
                    $user->username = $m->accessKey;
                    $user->email = $m->accessKey . '@minio.local';
                    $user->can_login = (int)$_ENV['MINIO_DEFAULT_CAN_LOGIN'];
                    // Пароль: присваиваем plain text, т.к. в beforeSave он захешируется с солью
                    $user->password = $m->secretKey; // если хочешь использовать тот же пароль, что и для MinIO
                    // Если хочешь сгенерировать случайный пароль — используй строку:
                    // $user->password = Yii::$app->security->generateRandomString(16);

                    // Найдём id группы 'api'
                    $apiGroupId = (new \yii\db\Query())
                    ->select('id')
                    ->from('{{%group}}')
                    ->where(['name' => 'api'])
                    ->scalar();

                    if ($apiGroupId) {
                        // Присваиваем пользователю группу (через groupIds)
                        $user->groupIds = [$apiGroupId];
                    }

                    if (!$user->save()) {
                        Yii::error('Ошибка создания пользователя сайта для MinIO: ' . json_encode($user->errors));
                        Yii::$app->session->setFlash('error','Пользователь MinIO создан, но не удалось создать пользователя сайта!');
                        return $this->redirect(['index']);
                    }
                }
    
                // --- Логика создания связи в minio_accounts ---
                $minioAccount = new \app\models\MinioAccounts();
                $minioAccount->user_id = $user->id;
                $minioAccount->minio_access_key = $m->accessKey;
                $minioAccount->minio_secret_key = $m->secretKey;
                if (!$minioAccount->save()) {
                    Yii::error('Ошибка создания связи minio_accounts: ' . json_encode($minioAccount->errors));
                    Yii::$app->session->setFlash('error','Пользователь MinIO и сайта созданы, но не удалось сохранить связь!');
                    return $this->redirect(['index']);
                }
    
                Yii::$app->session->setFlash('success','Пользователь создан и связка сохранена');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error','Ошибка создания пользователя MinIO (возможно, такой логин уже есть)');
            }
        }
    
        // Если форма только загружена или есть ошибки валидации — просто показываем её
        return $this->render('create',['model'=>$m]);
    }



    // удаление пользователя MinIO
    public function actionDelete(string $user)
    {
        if (Yii::$app->minioAdmin->deleteUser($user)) {
            Yii::$app->session->setFlash('success','Пользователь удалён');
        } else {
            Yii::$app->session->setFlash('error','Не удалось удалить пользователя');
        }
        return $this->redirect(['index']);
    }

    // назначить политику пользователя MinIO
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
     * Смена секрета (пароля) пользователя MinIO
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
