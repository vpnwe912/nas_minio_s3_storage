<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\MinioInstallForm;

class MinioInstallController extends Controller
{
    public function actionIndex()
    {
        $model = new MinioInstallForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $result = $model->processInstall();
            Yii::$app->session->setFlash('result', $result);
            return $this->refresh();
        }

        return $this->render('index', [
            'model' => $model,
            'serviceStatus' => $model->getServiceStatus(),
        ]);
    }
}


