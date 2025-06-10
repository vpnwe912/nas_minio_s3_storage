<?php
namespace app\controllers;

use Yii;
use app\models\MinioConfigs;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class MinioConfigController extends Controller
{
    // Список (index)
    public function actionIndex()
    {
        $configs = MinioConfigs::find()->all();
        return $this->render('index', ['configs' => $configs]);
    }

    // Редактирование
    public function actionUpdate($id)
    {
        $model = MinioConfigs::findOne($id);
        if (!$model) throw new NotFoundHttpException('Конфиг не найден');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Конфиг обновлён');
            return $this->redirect(['index']);
        }
        return $this->render('update', ['model' => $model]);
    }

    // Добавление нового (опционально)
    public function actionCreate()
    {
        $model = new MinioConfigs();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Конфиг добавлен');
            return $this->redirect(['index']);
        }
        return $this->render('create', ['model' => $model]);
    }

    // Удаление (опционально)
    public function actionDelete($id)
    {
        $model = MinioConfigs::findOne($id);
        if ($model) $model->delete();
        return $this->redirect(['index']);
    }
}
