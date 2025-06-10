<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\MinioAccounts;
use app\models\MinioConfigs;

class ApiController extends Controller
{
    public $enableCsrfValidation = false; // для API запросов!

    public function actionGetMinioConfig()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $accessKey = Yii::$app->request->post('access_key');
        $secretKey = Yii::$app->request->post('secret_key');
    
        if (!$accessKey || !$secretKey) {
            return [
                'status' => 'error',
                'message' => 'Не переданы логин или пароль'
            ];
        }
    
        // 1. Проверяем в minio_accounts
        $minioAccount = \app\models\MinioAccounts::find()
            ->where(['minio_access_key' => $accessKey, 'minio_secret_key' => $secretKey])
            ->one();
    
        if (!$minioAccount) {
            return [
                'status' => 'error',
                'message' => 'Неверные логин или пароль'
            ];
        }
    
        // 2. Берём пользователя сайта
        $user = \app\models\User::findOne($minioAccount->user_id);
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Пользователь не найден'
            ];
        }
    
        // 3. Проверка permission access_api
        if (!$user->hasPermission('access_api')) {
            return [
                'status' => 'error',
                'message' => 'Нет доступа к API'
            ];
        }
    
        // 4. Берём дефолтный конфиг из minio_configs
        $configModel = \app\models\MinioConfigs::find()->where(['is_default' => 1])->one();
        if (!$configModel) {
            return [
                'status' => 'error',
                'message' => 'Не найден шаблон конфига'
            ];
        }
    
        // 5. Генерируем rclone-конфиг
        $config = "[{$accessKey}]
    type = s3
    provider = {$configModel->provider}
    access_key_id = {$accessKey}
    secret_access_key = {$secretKey}
    region = {$configModel->region}
    endpoint = {$configModel->endpoint}
    location_constraint = {$configModel->location_constraint}
    ";
    
        return [
            'status' => 'success',
            'config_name' => $accessKey,
            'config_content' => $config
        ];
    }
}
