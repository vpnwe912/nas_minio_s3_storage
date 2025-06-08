<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/* @var $group string */
/* @var $allUsers string[] */
/* @var $allPolicies string[] */
?>
<div class="container mt-4">
    <h1>Обновить группу «<?= Html::encode($group) ?>»</h1>
    <?php $f=ActiveForm::begin(); ?>

        <p><strong>Группа:</strong> <?= Html::encode($group) ?></p>

        <?= $f->field($model,'users')
            ->listBox(array_combine($allUsers,$allUsers), ['multiple'=>true,'size'=>6]) ?>

        <?= $f->field($model,'policies')
            ->listBox(array_combine($allPolicies,$allPolicies), ['multiple'=>true,'size'=>6])
            ->hint('Переключите политики для этой группы') ?>

        <div class="d-grid"><?= Html::submitButton('Сохранить',['class'=>'btn btn-primary']) ?></div>

    <?php ActiveForm::end(); ?>
</div>
