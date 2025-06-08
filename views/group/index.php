<?php
use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Группы';
?>
<div class="group-index container">
  <h1><?= Html::encode($this->title) ?></h1>
  <p><?= Html::a('Создать группу',['create'],['class'=>'btn btn-success']) ?></p>
  <?= GridView::widget([
    'dataProvider'=>$dataProvider,
    'columns'=>[
      'id',
      'name',
      ['class'=>'yii\grid\ActionColumn','template'=>'{update} {delete}'],
    ],
  ]) ?>
</div>
