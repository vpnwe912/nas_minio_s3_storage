<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Создать bucket';
$this->params['breadcrumbs'][] = ['label' => 'Buckets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bucket-create container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'name')
                ->textInput()
                ->hint('Имя должно быть уникальным, в нижнем регистре и может содержать дефис') ?>

        <div class="form-group">
            <?= Html::submitButton('Создать', ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
