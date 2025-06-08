<?php
use yii\bootstrap5\Html;

$this->title = 'Создать группу';
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-create container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', [
        'model'           => $model,
        'permissionsList' => $permissionsList,
    ]) ?>
</div>
