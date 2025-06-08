<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/* @var $model \yii\base\DynamicModel */
/* @var $allUsers string[] */
/* @var $allPolicies string[] */
$this->title = 'Создать MinIO-группу';
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model,'group')->textInput()
              ->hint('Только латинские буквы, цифры и дефис') ?>

        <?= $form->field($model,'users')
              ->listBox(array_combine($allUsers,$allUsers), [
                  'multiple'=>true,'size'=>6,
              ]) ?>

        <?= $form->field($model,'policies')
              ->listBox(array_combine($allPolicies,$allPolicies), [
                  'multiple'=>true,'size'=>6,
              ])
              ->hint('Выберите политики для группы') ?>

        <div class="d-grid">
            <?= Html::submitButton('Создать', ['class'=>'btn btn-success']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
