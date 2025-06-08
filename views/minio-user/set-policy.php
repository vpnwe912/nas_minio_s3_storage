<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = "Политика для {$user}";
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $f = ActiveForm::begin(); ?>
        <?= Html::dropDownList('policy', null, array_combine($policies,$policies), ['class'=>'form-select']) ?>
        <div class="mt-3"><?= Html::submitButton('Сохранить',['class'=>'btn btn-primary']) ?></div>
    <?php ActiveForm::end(); ?>
</div>
