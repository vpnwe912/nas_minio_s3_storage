<?php
namespace app\controllers;

use Yii;
use app\models\Group;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

class GroupController extends Controller
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
        $dp = new ActiveDataProvider(['query'=>Group::find(),'pagination'=>['pageSize'=>20]]);
        return $this->render('index',['dataProvider'=>$dp]);
    }

    public function actionCreate()
    {
        $model = new Group();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        $perms = Yii::$app->db
            ->createCommand('SELECT id, name FROM {{%permission}}')
            ->queryAll();
        $permissionsList = \yii\helpers\ArrayHelper::map($perms, 'id', 'name');
    
        return $this->render('create', [
            'model'           => $model,
            'permissionsList' => $permissionsList,
        ]);
    }
    
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        $perms = Yii::$app->db
            ->createCommand('SELECT id, name FROM {{%permission}}')
            ->queryAll();
        $permissionsList = \yii\helpers\ArrayHelper::map($perms, 'id', 'name');
    
        return $this->render('update', [
            'model'           => $model,
            'permissionsList' => $permissionsList,
        ]);
    }
    

    public function actionDelete($id)
    {
        if ($id == 1) {
            throw new \yii\web\ForbiddenHttpException('Нельзя удалить группу admin.');
        }
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($m = Group::findOne($id))!==null) return $m;
        throw new NotFoundHttpException('Группа не найдена.');
    }
}
