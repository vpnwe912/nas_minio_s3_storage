<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use app\models\Binary;
use yii\web\NotFoundHttpException;

class BinariesController extends Controller
{
    public function actionIndex()
    {
        $binaries = Binary::find()->orderBy(['updated_at' => SORT_DESC])->all();
        return $this->render('index', ['binaries' => $binaries]);
    }

    public function actionCreate()
    {
        $model = new Binary();

        if (Yii::$app->request->isPost) {
            $model->name = Yii::$app->request->post('name');
            $model->version = Yii::$app->request->post('version');
            $model->description = Yii::$app->request->post('description');
            $file = UploadedFile::getInstanceByName('file');

            if ($file && $model->name && $model->version) {
                $folder = 'downloads/' . $model->name;
                $fullFolder = Yii::getAlias('@app/' . $folder);
                if (!file_exists($fullFolder)) mkdir($fullFolder, 0775, true);

                $fileName = $file->baseName . '.' . $file->extension;
                $savePath = $fullFolder . '/' . $fileName;

                if ($file->saveAs($savePath)) {
                    $model->filename   = $fileName;
                    $model->type       = $file->extension;
                    $model->path       = $folder . '/' . $fileName;
                    $model->size       = $file->size;
                    $model->hash       = hash_file('sha256', $savePath);
                    $model->updated_at = date('Y-m-d H:i:s');
                    Binary::deleteAll(['name'=>$model->name, 'version'=>$model->version]);
                    $model->save(false);
                    Yii::$app->session->setFlash('success', 'Файл успешно загружен!');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Не удалось сохранить файл.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Заполните все поля и выберите файл.');
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = Binary::findOne($id);
        if (!$model) throw new NotFoundHttpException('Бинарь не найден');

        if (Yii::$app->request->isPost) {
            $model->name = Yii::$app->request->post('name');
            $model->version = Yii::$app->request->post('version');
            $model->description = Yii::$app->request->post('description');
            $file = UploadedFile::getInstanceByName('file');
            if ($file) {
                $folder = 'downloads/' . $model->name;
                $fullFolder = Yii::getAlias('@app/' . $folder);
                if (!file_exists($fullFolder)) mkdir($fullFolder, 0775, true);

                $fileName = $file->baseName . '.' . $file->extension;
                $savePath = $fullFolder . '/' . $fileName;
                if ($file->saveAs($savePath)) {
                    if (file_exists(Yii::getAlias('@app/' . $model->path))) {
                        @unlink(Yii::getAlias('@app/' . $model->path));
                    }
                    $model->filename = $fileName;
                    $model->type     = $file->extension;
                    $model->path     = $folder . '/' . $fileName;
                    $model->size     = $file->size;
                    $model->hash     = hash_file('sha256', $savePath);
                    $model->updated_at = date('Y-m-d H:i:s');
                }
            } else {
                $model->updated_at = date('Y-m-d H:i:s');
            }

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Файл обновлён!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка обновления файла.');
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = Binary::findOne($id);
        if ($model) {
            $filePath = Yii::getAlias('@app/' . $model->path);
            if (file_exists($filePath)) @unlink($filePath);
            $model->delete();
            Yii::$app->session->setFlash('success', 'Файл удалён!');
        }
        return $this->redirect(['index']);
    }
}

