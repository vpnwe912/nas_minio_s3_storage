<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login container mt-5" style="max-width: 400px;">
    <h1 class="mb-4"><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to login:</p>

    <?php $form = ActiveForm::begin([
        'id'                     => 'login-form',
        'layout'                 => 'floating', // для плавающих меток (опционально)
        'fieldConfig' => [
            'options' => ['class' => 'form-group mb-3'],
        ],
    ]); ?>

        <?= $form->field($model, 'username')
                ->textInput(['autofocus' => true, 'placeholder' => 'Login']) ?>

        <?= $form->field($model, 'password')
                ->passwordInput(['placeholder' => 'Password']) ?>

        <?= $form->field($model, 'rememberMe')->checkbox([
            'template' => "<div class=\"form-check\">\n{input} {label}\n</div>\n{error}",
        ]) ?>

        <div class="d-grid">
            <?= Html::submitButton('Login', [
                    'class' => 'btn btn-primary btn-lg',
                    'name'  => 'login-button'
            ]) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
