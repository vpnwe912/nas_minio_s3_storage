<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use yii\base\DynamicModel;
use yii\web\ForbiddenHttpException;

class ObjectController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['browse','create-folder','upload','delete'],
                'rules'=>[
                    [
                        'actions'=>['browse'],
                        'allow'=>true,'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('viewBuckets'),
                    ],
                    [
                        'actions'=>['create-folder','upload'],
                        'allow'=>true,'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('uploadFiles'),
                    ],
                    [
                        'actions'=>['delete'],
                        'allow'=>true,'roles'=>['@'],
                        'matchCallback'=>fn() => Yii::$app->user->identity->hasPermission('deleteFiles'),
                    ],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    /**
     * Просмотр содержимого prefix внутри bucket
     */
    public function actionBrowse(string $bucket, string $prefix = '')
    {
        $data = Yii::$app->minio->listObjects($bucket, $prefix);

        // модель для создания папки
        $folderModel = new DynamicModel(['name']);
        $folderModel->addRule('name','required')
                    ->addRule('name','match',[
                        'pattern'=>'/^[^\/]+$/',
                        'message'=>'Имя папки не должно содержать слэш'
                    ]);

        // модель для загрузки файлов
        $uploadModel = new DynamicModel(['files']);
        $uploadModel->addRule('files','each',['rule'=>['file']]);

        return $this->render('browse', [
            'bucket'      => $bucket,
            'prefix'      => $prefix,
            'folders'     => $data['folders'],
            'objects'     => $data['objects'],
            'folderModel' => $folderModel,
            'uploadModel' => $uploadModel,
        ]);
    }

    /**
     * Создание «папки» внутри текущего префикса
     */
    public function actionCreateFolder(string $bucket, string $prefix)
    {
        $name = Yii::$app->request->post('DynamicModel')['name'] ?? null;
        if ($name) {
            $key = ($prefix === '' ? '' : $prefix) . $name . '/';
            if (Yii::$app->minio->createFolder($bucket, $key)) {
                Yii::$app->session->setFlash('success','Папка создана');
            } else {
                Yii::$app->session->setFlash('error','Ошибка создания папки');
            }
        }
        return $this->redirect(['browse','bucket'=>$bucket,'prefix'=>$prefix]);
    }

    /**
     * Загрузка файлов
     */
    public function actionUpload(string $bucket, string $prefix)
    {
        $files = UploadedFile::getInstancesByName('files');
        foreach ($files as $file) {
            $key = ($prefix === '' ? '' : $prefix) . $file->name;
            if (Yii::$app->minio->uploadFile($bucket, $key, $file->tempName)) {
                Yii::$app->session->addFlash('success', "Файл {$file->name} загружен");
            } else {
                Yii::$app->session->addFlash('error', "Ошибка загрузки {$file->name}");
            }
        }
        return $this->redirect(['browse','bucket'=>$bucket,'prefix'=>$prefix]);
    }

    /**
     * Удаление объекта или папки (если пустая)
     */
    public function actionDelete(string $bucket, string $key)
    {
        if (Yii::$app->minio->deleteObject($bucket, $key)) {
            Yii::$app->session->setFlash('success','Удалено');
        } else {
            Yii::$app->session->setFlash('error','Не удалось удалить (не пусто или ошибка)');
        }
        return $this->redirect(['browse','bucket'=>$bucket,'prefix'=>Yii::$app->request->get('prefix')]);
    }
}
