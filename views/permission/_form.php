<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Permission */

$form = ActiveForm::begin();
echo $form->field($model, 'name')->textInput(['maxlength' => true]);
echo Html::submitButton(
    $model->isNewRecord ? 'Создать' : 'Сохранить',
    ['class' => 'btn btn-primary']
);
ActiveForm::end();
