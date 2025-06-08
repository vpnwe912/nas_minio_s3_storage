<?php
namespace app\controllers;

use Yii;
use app\models\Permission;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

class PermissionController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','update','delete'],
                'rules'=>[['actions'=>['index','create','update','delete'],'allow'=>true,'roles'=>['@']]],
            ],
        ];
    }

    public function actionIndex()
    {
        $dp = new ActiveDataProvider(['query'=>Permission::find(),'pagination'=>['pageSize'=>20]]);
        return $this->render('index',['dataProvider'=>$dp]);
    }

    public function actionCreate()
    {
        $model = new Permission();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        return $this->render('create',['model'=>$model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        return $this->render('update',['model'=>$model]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($m=Permission::findOne($id))!==null) return $m;
        throw new NotFoundHttpException('Право не найдено.');
    }
}
