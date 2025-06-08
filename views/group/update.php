<?php
use yii\bootstrap5\Html;

$this->title = 'Редактирование группы: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-update container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', [
        'model'           => $model,
        'permissionsList' => $permissionsList,
    ]) ?>
</div>
