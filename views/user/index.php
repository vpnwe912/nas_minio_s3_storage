<?php
use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Пользователи';
?>
<div class="user-index container">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать пользователя', ['create'], ['class'=>'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'username',
            'email',
            [
                'label'=>'Группы',
                'value'=> function($m) {
                    return implode(', ', \yii\helpers\ArrayHelper::getColumn($m->groups,'name'));
                },
            ],
            ['class'=>'yii\grid\ActionColumn',
             'template'=>'{update} {delete}',
            ],
        ],
    ]); ?>

</div>
