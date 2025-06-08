<?php
use yii\bootstrap5\Html;

$this->title = 'Создать пользователя';
?>
<div class="user-create container">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model'=>$model,'groupsList'=>$groupsList]) ?>
</div>
