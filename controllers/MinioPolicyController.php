<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;

class MinioPolicyController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','update','delete'],
                'rules'=>[
                    ['actions'=>['index'],  'allow'=>true,'roles'=>['@']],
                    ['actions'=>['create'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['update'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['delete'], 'allow'=>true,'roles'=>['@']],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    // список политик
    public function actionIndex()
    {
        $policies = Yii::$app->minioAdmin->listPolicies();
        return $this->render('index', ['policies'=>$policies]);
    }

    // создание новой политики
    public function actionCreate()
    {
        $model = new DynamicModel(['name','json']);
        $model->addRule(['name','json'],'required')
              ->addRule('name','match',['pattern'=>'/^[A-Za-z0-9\-]+$/'])
              ->addRule('json','string');

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // сохраняем JSON во временный файл
            $tmpDir = Yii::getAlias('@runtime/minio-policies');
            FileHelper::createDirectory($tmpDir);
            $file = "{$tmpDir}/{$model->name}.json";
            file_put_contents($file, $model->json);

            if (Yii::$app->minioAdmin->createPolicy($model->name, $file)) {
                Yii::$app->session->setFlash('success','Политика создана');
            } else {
                Yii::$app->session->setFlash('error','Ошибка создания политики');
            }
            return $this->redirect(['index']);
        }

        return $this->render('create',['model'=>$model]);
    }

    // удаление политики
    public function actionDelete(string $name)
    {
        if (Yii::$app->minioAdmin->deletePolicy($name)) {
            Yii::$app->session->setFlash('success','Политика удалена');
        } else {
            Yii::$app->session->setFlash('error','Ошибка удаления политики');
        }
        return $this->redirect(['index']);
    }

        /**
     * Редактирование JSON-политики
     */
    public function actionUpdate(string $name)
    {
        $service = Yii::$app->minioAdmin;
        $tmpDir  = Yii::getAlias('@runtime/minio-policies');
        FileHelper::createDirectory($tmpDir);
        $file    = "{$tmpDir}/{$name}.json";
    
        // --- Экспортим текущую политику в файл
        if (! $service->exportPolicy($name, $file)) {
            Yii::$app->session->setFlash('error','Не удалось получить политику для редактирования');
            return $this->redirect(['index']);
        }
    
        // --- Готовим модель для формы
        $model = new DynamicModel(['json']);
        $model->addRule('json','required');
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // раскрашиваем (pretty print) и сохраняем табуляции
            $pretty = json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            $model->json = $pretty;
        } else {
            // если вдруг не valid JSON — оставляем как есть
            $model->json = $raw;
        }
    
        // --- Обработка POST
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Перезаписываем файл
            file_put_contents($file, $model->json);
    
            // Удаляем старую политику и создаём новую
            $service->deletePolicy($name);
            if ($service->createPolicy($name, $file)) {
                Yii::$app->session->setFlash('success',"Политика «{$name}» обновлена");
            } else {
                Yii::$app->session->setFlash('error',"Ошибка обновления политики");
            }
            return $this->redirect(['index']);
        }
    
        // --- Рендерим форму редактирования
        return $this->render('update', [
            'name'  => $name,
            'model' => $model,
        ]);
    }
    
}
