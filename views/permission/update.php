<?php
use yii\bootstrap5\Html;

$this->title = 'Редактировать право: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Права', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="permission-update container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
