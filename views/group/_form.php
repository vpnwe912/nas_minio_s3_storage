<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var $groupsList array **/
/** @var $permissionsList array **/

$form = ActiveForm::begin();
// поле имени
echo $form->field($model,'name')->textInput();
// список прав
echo $form->field($model,'permissionIds')
    ->listBox($permissionsList, ['multiple'=>true,'size'=>10]);
echo Html::submitButton(
    $model->isNewRecord ? 'Создать' : 'Сохранить',
    ['class'=>'btn btn-primary']
);
ActiveForm::end();
