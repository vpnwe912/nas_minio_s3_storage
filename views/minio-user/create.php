<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Создать MinIO-пользователя';
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $f = ActiveForm::begin(); ?>
        <?= $f->field($model,'accessKey')->textInput() ?>
        <?= $f->field($model,'secretKey')->passwordInput() ?>
        <div class="d-grid"><?= Html::submitButton('Создать',['class'=>'btn btn-primary']) ?></div>
    <?php ActiveForm::end(); ?>
</div>
