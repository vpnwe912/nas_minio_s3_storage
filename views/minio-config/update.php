<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Редактировать конфиг: ' . $model->name;
?>

<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'provider') ?>
        <?= $form->field($model, 'endpoint') ?>
        <?= $form->field($model, 'region') ?>
        <?= $form->field($model, 'location_constraint') ?>
        <?= $form->field($model, 'comment')->textarea() ?>
        <?= $form->field($model, 'is_default')->checkbox() ?>

        <div class="d-grid gap-2 mt-3">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
