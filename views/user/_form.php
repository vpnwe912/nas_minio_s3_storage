<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var $this yii\web\View **/
/** @var $model app\models\User **/
/** @var $groupsList array id=>name **/

$form = ActiveForm::begin();
echo $form->field($model, 'username')->textInput();
echo $form->field($model, 'email')->input('email');
echo $form->field($model, 'password')->passwordInput();

// мультиселект групп
echo $form->field($model, 'groupIds')
    ->listBox($groupsList, ['multiple'=>true, 'size'=>5]);

echo Html::submitButton(
    $model->isNewRecord ? 'Создать' : 'Обновить',
    ['class'=>'btn btn-primary']
);
ActiveForm::end();
