<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = "Обновить секрет для «{$user}»";
$this->params['breadcrumbs'][] = ['label'=>'MinIO Пользователи','url'=>['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

        <p><strong>Пользователь:</strong> <?= Html::encode($user) ?></p>

        <?= $form->field($model, 'secretKey')
                ->passwordInput()
                ->hint('Минимум 8 символов') ?>

        <div class="d-grid">
            <?= Html::submitButton('Сохранить', ['class'=>'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
