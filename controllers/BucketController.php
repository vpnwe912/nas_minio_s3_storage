<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;

class BucketController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','delete'],
                'rules'=>[
                    [
                        'actions'=>['index'],
                        'allow'=>true,
                        'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('viewBuckets'),
                    ],
                    [
                        'actions'=>['create'],
                        'allow'=>true,
                        'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('createBuckets'),
                    ],
                    [
                        'actions'=>['delete'],
                        'allow'=>true,
                        'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('deleteBuckets'),
                    ],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    /**
     * Список бакетов
     */
    public function actionIndex()
    {
        $buckets = Yii::$app->minio->listBuckets();
        return $this->render('index', [
            'buckets' => $buckets,
        ]);
    }

    /**
     * Создать бакет
     */
    public function actionCreate()
    {
        // динамическая модель для формы
        $model = new DynamicModel(['name']);
        $model->addRule('name', 'required')
              ->addRule('name', 'match', [
                  'pattern' => '/^[a-z0-9\-]+$/',
                  'message' => 'Допустимы только строчные латинские буквы, цифры и дефис',
              ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if (Yii::$app->minio->createBucket($model->name)) {
                Yii::$app->session->setFlash('success', "Bucket «{$model->name}» создан");
            } else {
                Yii::$app->session->setFlash('error', "Ошибка создания bucket «{$model->name}»");
            }
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Удалить бакет (только если пуст)
     */
    public function actionDelete($name)
    {
        if (Yii::$app->minio->deleteBucket($name)) {
            Yii::$app->session->setFlash('success', "Bucket «{$name}» удалён");
        } else {
            Yii::$app->session->setFlash('error', "Невозможно удалить bucket «{$name}»: он не пуст или произошла ошибка");
        }
        return $this->redirect(['index']);
    }
}
