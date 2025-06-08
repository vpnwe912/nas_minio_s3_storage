<?php
use yii\bootstrap5\Html;
$this->title = "Группа: {$model->name}";
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;
?>
