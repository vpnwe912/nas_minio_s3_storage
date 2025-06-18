<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = "Установка MinIO";
?>
<h2>Установка MinIO через веб-интерфейс</h2>

<?php if (Yii::$app->session->hasFlash('result')): ?>
    <div class="alert alert-success">
        <?= Yii::$app->session->getFlash('result') ?>
    </div>
<?php endif; ?>

<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'installType')->dropDownList([
    'download' => 'Скачать с официального сайта',
    'copy' => 'Использовать локальный файл',
]) ?>
<?= $form->field($model, 'minioLocalFile')->textInput(['readonly' => true])->label('Локальный путь к minio-server-debian') ?>
<?= $form->field($model, 'mcLocalFile')->textInput(['readonly' => true])->label('Локальный путь к minio-client-debian') ?>

<?= $form->field($model, 'minioUser') ?>
<?= $form->field($model, 'minioDir') ?>
<?= $form->field($model, 'dataDir') ?>
<?= $form->field($model, 'serviceName') ?>
<?= $form->field($model, 'minioPath') ?>
<?= $form->field($model, 'rootUser') ?>
<?= $form->field($model, 'rootPassword')->passwordInput() ?>

<div class="form-group">
    <?= Html::submitButton('Установить', ['class' => 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>

<hr>
<h4>Статус сервиса MinIO</h4>
<code style="white-space:pre-wrap;"><?= $serviceStatus ?></code>
