<?php
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $policies array */
?>
<h1>Политики MinIO</h1>

<p><?= Html::a('Создать новую политику', ['create'], ['class'=>'btn btn-success']) ?></p>

<?= GridView::widget([
    'dataProvider' => new \yii\data\ArrayDataProvider([
        'allModels'  => $policies,
        'pagination' => false,
    ]),
    'columns' => [
        'policy',
        [
            'attribute' => 'comments',
            'label'     => 'Комментарии',
            'format'    => 'ntext',  // многострочный текст
        ],
        [
            'class'   => 'yii\grid\ActionColumn',
            'template'=> '{update} {delete}',
            'buttons' => [
                'update' => fn($url,$model) =>
                    Html::a('✎', ['update','name'=>$model['policy']]),
                'delete' => fn($url,$model) =>
                    Html::a('🗑', ['delete','name'=>$model['policy']], [
                        'data'=>['method'=>'post','confirm'=>'Удалить?']
                    ]),
            ],
        ],
    ],
]) ?>

