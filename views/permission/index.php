<?php
use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Права';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="permission-index container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать право', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'name',
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{update} {delete}',
            ],
        ],
    ]); ?>
</div>
