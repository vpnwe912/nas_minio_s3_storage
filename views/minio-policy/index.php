<?php

use yii\helpers\Html;

$this->title = 'Политики MinIO';
$this->params['breadcrumbs'][] = $this->title;
?>
<table class="table table-bordered">

<div class="card-header">

            <div class="float-left">
                <?= Html::a('Создать политику', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
        </div>

    <tr>
        <th>Название</th>
        <th>Комментарий</th>
        <th>Действия</th>
    </tr>
    <?php foreach($policies as $policyName): ?>
        <tr>

            <td><?= Html::encode($policyName) ?></td>
            <td><?= Html::encode(\app\models\PolicyMeta::getPolicyComment($policyName)) ?></td>
            <td>
                <?= Html::a('Редактировать', ['update', 'name' => $policyName], ['class'=>'btn btn-sm btn-primary']) ?>
                <?= Html::a('Удалить', ['delete', 'name' => $policyName], [
                    'class'=>'btn btn-sm btn-danger',
                    'data-confirm'=>'Точно удалить?'
                ]) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
