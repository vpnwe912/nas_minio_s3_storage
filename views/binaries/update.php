<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $model app\models\Binary */

$this->title = 'Редактирование файла: ' . $model->name;
?>
<div class="container py-4">
    <h2><?= Html::encode($this->title) ?></h2>
    <form method="post" enctype="multipart/form-data" action="<?= Url::to(['binaries/update', 'id'=>$model->id]) ?>">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <input type="text" class="form-control" name="name" value="<?= Html::encode($model->name) ?>" required>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" name="version" value="<?= Html::encode($model->version) ?>" required>
            </div>
            <div class="col-auto">
                <input type="file" class="form-control" name="file">
                <div class="form-text">Оставьте пустым, чтобы не менять файл</div>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" name="description" value="<?= Html::encode($model->description) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-warning">Сохранить</button>
                <a href="<?= Url::to(['binaries/index']) ?>" class="btn btn-secondary">Назад</a>
            </div>
        </div>
        <div class="mt-3">
            <b>Текущий файл:</b> <?= Html::encode($model->filename) ?>, <?= Yii::$app->formatter->asShortSize($model->size) ?>
            <br>
            <b>SHA256:</b> <span style="font-size:12px;"><?= Html::encode($model->hash) ?></span>
            <br>
            <a href="<?= Url::to(['/api/download/'.$model->filename]) ?>" target="_blank" class="btn btn-primary btn-sm mt-2">Скачать текущий файл</a>
        </div>
    </form>
</div>
