<?php
use yii\bootstrap5\Html;

$this->title = 'MinIO Политики';
?>
<div class="mt-4">

    <p><?= Html::a('Создать политику', ['create'], ['class'=>'btn btn-success']) ?></p>

    <table class="table table-striped">
        <thead>
            <tr><th>Имя</th><th>Действия</th></tr>
        </thead>
        <tbody>
        <?php foreach ($policies as $p): ?>
            <tr>
            <td><?= Html::encode($p['policy']) ?></td>
            <td>
                <?= Html::a('Редактировать', ['update','name'=>$p['policy']], [
                    'class'=>'btn btn-sm btn-primary',
                ]) ?>
                <?= Html::a('Удалить', ['delete','name'=>$p['policy']], [
                    'class'=>'btn btn-sm btn-danger',
                    'data'=>['method'=>'post','confirm'=>'Удалить политику?'],
                ]) ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
