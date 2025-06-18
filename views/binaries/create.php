<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Загрузка бинарного файла';
?>
<div class="container py-4">
    <h2><?= Html::encode($this->title) ?></h2>
    <form method="post" enctype="multipart/form-data" action="<?= Url::to(['binaries/create']) ?>">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <input type="text" class="form-control" name="name" placeholder="Название (например, rclone)" required>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" name="version" placeholder="Версия (например, 1.66.0)" required>
            </div>
            <div class="col-auto">
                <input type="file" class="form-control" name="file" required>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" name="description" placeholder="Описание (опционально)">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success">Загрузить</button>
                <a href="<?= Url::to(['binaries/index']) ?>" class="btn btn-secondary">Назад</a>
            </div>
        </div>
    </form>
</div>
