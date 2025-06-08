<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Создать MinIO-политику';
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $f = ActiveForm::begin(); ?>

        <?= $f->field($model,'name')
              ->textInput()
              ->hint('Только латинские буквы, цифры и дефис') ?>

        <?= $f->field($model,'json')
              ->textarea(['rows'=>10])
              ->hint('Вставьте JSON-политику согласно MinIO S3-совместимому формату') ?>

        <div class="d-grid">
            <?= Html::submitButton('Создать', ['class'=>'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
