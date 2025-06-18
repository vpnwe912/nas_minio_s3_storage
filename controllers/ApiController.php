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

    public function actionLogin()
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
    
        // Ищем аккаунт MinIO
        $minioAccount = \app\models\MinioAccounts::find()
            ->where(['minio_access_key' => $accessKey, 'minio_secret_key' => $secretKey])
            ->one();
    
        if (!$minioAccount) {
            return [
                'status' => 'error',
                'message' => 'Неверные логин или пароль'
            ];
        }
    
        $user = \app\models\User::findOne($minioAccount->user_id);
        if (!$user || !$user->can_login) {
            return [
                'status' => 'error',
                'message' => 'Ваша учетная запись отключена'
            ];
        }
    
        // Стираем старые токены (1 пользователь — 1 токен)
        \app\models\UserToken::deleteAll(['user_id' => $user->id]);
    
        // Генерим новый токен
        $token = bin2hex(random_bytes(32));
        $userToken = new \app\models\UserToken();
        $userToken->user_id = $user->id;
        $userToken->token = $token;
        $userToken->created_at = time();
        $userToken->save(false);
    
        return [
            'status' => 'success',
            'token' => $token,
            'user'  => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]
        ];
    }
    
    private function getUserByToken($token)
    {
        if (!$token) return null;
        $userToken = \app\models\UserToken::findOne(['token' => $token]);
        if (!$userToken) return null;
        $user = \app\models\User::findOne($userToken->user_id);
        if (!$user || !$user->can_login) return null;
        return $user;
    }

    public function actionHealth()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $token = Yii::$app->request->post('token');
    
        $userToken = \app\models\UserToken::findOne(['token' => $token]);
        if ($userToken) {
            $user = \app\models\User::findOne($userToken->user_id);
            if ($user && $user->can_login) {
                return ['status' => 'work'];
            }
        }
        return [
            'status' => 'error',
            'message' => 'Недействительный токен или пользователь отключён'
        ];
    }

    public function actionGetMinioConfig()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $token = Yii::$app->request->post('token');

        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Не передан токен'
            ];
        }

        // Находим токен и пользователя
        $userToken = \app\models\UserToken::findOne(['token' => $token]);
        if (!$userToken) {
            return [
                'status' => 'error',
                'message' => 'Недействительный токен'
            ];
        }
        $user = \app\models\User::findOne($userToken->user_id);
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Пользователь не найден'
            ];
        }
        if (!$user->can_login) {
            return [
                'status' => 'error',
                'message' => 'Ваша учетная запись отключена'
            ];
        }
        if (!$user->hasPermission('access_api')) {
            return [
                'status' => 'error',
                'message' => 'Нет доступа к API'
            ];
        }

        // Теперь находим аккаунт MinIO (по user_id)
        $minioAccount = \app\models\MinioAccounts::find()->where(['user_id' => $user->id])->one();
        if (!$minioAccount) {
            return [
                'status' => 'error',
                'message' => 'Нет учётной записи MinIO для пользователя'
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
        $config = "[{$minioAccount->minio_access_key}]
        type = s3
        provider = {$configModel->provider}
        access_key_id = {$minioAccount->minio_access_key}
        secret_access_key = {$minioAccount->minio_secret_key}
        region = {$configModel->region}
        endpoint = {$configModel->endpoint}
        location_constraint = {$configModel->location_constraint}
        ";

        return [
            'status' => 'success',
            'config_name' => $minioAccount->minio_access_key,
            'config_content' => $config
        ];
    }


    public function actionAccess()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $token = Yii::$app->request->post('token');
        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Нет токена'
            ];
        }

        $userToken = \app\models\UserToken::findOne(['token' => $token]);
        if (!$userToken) {
            return [
                'status' => 'error',
                'message' => 'Недействительный токен'
            ];
        }
        $user = \app\models\User::findOne($userToken->user_id);
        if (!$user || !$user->can_login) {
            return [
                'status' => 'error',
                'message' => 'Пользователь не найден или отключён'
            ];
        }
        $minioAccount = \app\models\MinioAccounts::find()->where(['user_id' => $user->id])->one();
        if (!$minioAccount) {
            return [
                'status' => 'error',
                'message' => 'Нет MinIO-аккаунта для пользователя'
            ];
        }
        $minioUser = $minioAccount->minio_access_key;

        $minioAdmin = new \app\components\MinioAdminService();

        $userPolicies = [];
        $users = $minioAdmin->listUsers();
        foreach ($users as $usr) {
            if ($usr['user'] === $minioUser) {
                $userPolicies = array_filter(array_map('trim', explode(',', $usr['policies'])));
                break;
            }
        }

        $groupPolicies = [];
        $groups = $minioAdmin->listGroups();
        foreach ($groups as $group) {
            $usersInGroup = array_map('trim', explode(',', $group['users']));
            if (in_array($minioUser, $usersInGroup)) {
                $groupPolicies = array_merge($groupPolicies, array_filter(array_map('trim', explode(',', $group['policies']))));
            }
        }

        $allPolicies = array_unique(array_merge($userPolicies, $groupPolicies));

        // === Вот тут собираем только ресурсы! ===
        $resources = [];
        foreach ($allPolicies as $policyName) {
            if (!$policyName) continue;
            $policy = $minioAdmin->getPolicy($policyName);
            if (isset($policy['Statement'])) {
                foreach ($policy['Statement'] as $stmt) {
                    if (isset($stmt['Resource'])) {
                        foreach ($stmt['Resource'] as $res) {
                            // Парсим arn:aws:s3:::bucket[/folder/*]
                            if (preg_match('~^arn:aws:s3:::(.+)$~', $res, $m)) {
                                $full = $m[1]; // bucket или bucket/path/*
                                $parts = explode('/', $full, 2);
                                $bucket = $parts[0];
                                $path = isset($parts[1]) ? $parts[1] : '';
                                // Убираем лишние *
                                $path = rtrim($path, '*');
                                $path = rtrim($path, '/');
                                $resources[] = [
                                    'bucket' => $bucket,
                                    'path'   => $path,
                                    // для rclone: minio:bucket/path
                                    'rclone_path' => ($path !== '') ? "minio:$bucket/$path" : "minio:$bucket",
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Оставляем только уникальные ресурсы
        $uniqueResources = [];
        foreach ($resources as $res) {
            $key = $res['rclone_path'];
            $uniqueResources[$key] = $res;
        }

        return [
            'status' => 'success',
            'resources' => array_values($uniqueResources), // только ресурсы, без политик!
        ];
    }


    public function actionLogout()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $token = Yii::$app->request->post('token');
        if (!$token) {
            return ['status' => 'error', 'message' => 'Нет токена'];
        }

        // Удаляем токен (можно через deleteAll — быстрее)
        \app\models\UserToken::deleteAll(['token' => $token]);

        // Всегда отдаём success
        return ['status' => 'success'];
    }


    // ---------------------------- DOWNLOADS ----------------------------
    public function actionDownloadList()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $binaries = \app\models\Binary::find()
            ->orderBy(['name' => SORT_ASC, 'version' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($binaries as $bin) {
            $result[] = [
                'id'        => $bin->id,
                'name'      => $bin->name,
                'filename'  => $bin->filename,
                'version'   => $bin->version,
                'size'      => $bin->size,
                'type'      => $bin->type,
                'url'       => "/api/download/{$bin->filename}", // Для скачивания
                'hash'      => $bin->hash,
                'updated_at'=> $bin->updated_at,
                'description'=> $bin->description,
            ];
        }

        return $result;
    }

    public function actionDownload($filename)
    {
        $binary = \app\models\Binary::findOne(['filename' => $filename]);
        if (!$binary) {
            throw new \yii\web\NotFoundHttpException("Файл не найден");
        }

        $fullPath = Yii::getAlias('@app/' . $binary->path);
        if (!file_exists($fullPath)) {
            throw new \yii\web\NotFoundHttpException("Файл отсутствует на сервере");
        }

        return Yii::$app->response->sendFile($fullPath, $binary->filename, [
            'mimeType' => 'application/octet-stream',
            'inline' => false,
        ]);
    }

    // POST /api/upload-binary
    // FormData: file=<файл> name=rclone version=1.66.0 description=...
    public function actionUploadBinary()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Здесь должен быть check на админскую роль
        $token = Yii::$app->request->post('token');
        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Нет токена'
            ];
        }

        $file = \yii\web\UploadedFile::getInstanceByName('file');
        $name = Yii::$app->request->post('name');
        $version = Yii::$app->request->post('version');
        $description = Yii::$app->request->post('description');

        if (!$file || !$name || !$version) {
            return ['status' => 'error', 'message' => 'Не хватает параметров'];
        }

        $folder = 'downloads/' . $name;
        $fullFolder = Yii::getAlias('@app/' . $folder);
        if (!file_exists($fullFolder)) mkdir($fullFolder, 0775, true);

        $savePath = $fullFolder . '/' . $file->name;
        if (!$file->saveAs($savePath)) {
            return ['status' => 'error', 'message' => 'Не удалось сохранить файл'];
        }

        $hash = hash_file('sha256', $savePath);
        $size = filesize($savePath);
        $type = pathinfo($file->name, PATHINFO_EXTENSION);

        // Если уже была такая версия — удаляем
        \app\models\Binary::deleteAll(['name' => $name, 'version' => $version]);

        $binary = new \app\models\Binary([
            'name'       => $name,
            'filename'   => $file->name,
            'version'    => $version,
            'type'       => $type,
            'path'       => $folder . '/' . $file->name,
            'size'       => $size,
            'hash'       => $hash,
            'description'=> $description,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $binary->save(false);

        return ['status' => 'success', 'id' => $binary->id];
    }

    public function actionDeleteBinary($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Здесь должен быть check на админскую роль
        $token = Yii::$app->request->post('token');
        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Нет токена'
            ];
        }

        $binary = \app\models\Binary::findOne($id);
        if (!$binary) return ['status' => 'error', 'message' => 'Файл не найден'];
        $filePath = Yii::getAlias('@app/' . $binary->path);
        if (file_exists($filePath)) unlink($filePath);
        $binary->delete();

        return ['status' => 'success'];
    }


    public function actionBinariesVersion()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Только последние версии каждого бинаря
        $binaries = \app\models\Binary::find()
            ->select(['name', 'version', 'hash', 'updated_at', 'filename'])
            ->groupBy(['name'])
            ->all();

        $result = [];
        foreach ($binaries as $bin) {
            $result[$bin->name] = [
                'version'    => $bin->version,
                'filename'   => $bin->filename,
                'hash'       => $bin->hash,
                'updated_at' => $bin->updated_at,
            ];
        }
        return $result;
    }



    public function actionDownloadRclone()
    {
        $file = Yii::getAlias('@app/downloads/rclone/rclone.exe'); // путь к файлу

        if (!file_exists($file)) {
            return $this->asJson(['status' => 'error', 'message' => 'Файл не найден']);
        }

        return Yii::$app->response->sendFile($file, 'rclone.exe', [
            'mimeType' => 'application/octet-stream',
            'inline' => false
        ]);
    }

    public function actionDownloadWinfsp()
    {
        $file = Yii::getAlias('@app/downloads/winfsp/winfsp.msi'); // путь к файлу

        if (!file_exists($file)) {
            return $this->asJson(['status' => 'error', 'message' => 'Файл не найден']);
        }

        return Yii::$app->response->sendFile($file, 'winfsp.msi', [
            'mimeType' => 'application/octet-stream',
            'inline' => false
        ]);
    }

    // --------------------------- END DOWNLOADS ---------------------------

    // ---------------------------- TOKENS ----------------------------
    public function actionCheckToken()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $token = Yii::$app->request->post('token');
        
        if (!$token) {
            return [
                'status' => 'error',
                'message' => 'Токен не передан'
            ];
        }
        
        // Находим запись с токеном
        $tokenRecord = \app\models\UserToken::findOne(['token' => $token]);
        
        if (!$tokenRecord) {
            return [
                'status' => 'error',
                'message' => 'Неверный токен'
            ];
        }
        
        // Находим пользователя
        $user = \app\models\User::findOne($tokenRecord->user_id);
        
        if (!$user || !$user->can_login) {
            return [
                'status' => 'error',
                'message' => 'Пользователь не найден или учетная запись отключена'
            ];
        }
        
        // Если всё ок, возвращаем успешный ответ
        return [
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email
                // Другие нужные поля
            ]
        ];
    }



}
