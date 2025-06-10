<?php
use yii\bootstrap5\Html;

/** @var $configs \app\models\MinioConfigs[] */
$this->title = 'Список MinIO-конфигов';
?>

<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= Html::a('Добавить конфиг', ['create'], ['class' => 'btn btn-success mb-3']) ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Endpoint</th>
                <th>Регион</th>
                <th>По умолчанию</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($configs as $cfg): ?>
            <tr>
                <td><?= $cfg->id ?></td>
                <td><?= Html::encode($cfg->name) ?></td>
                <td><?= Html::encode($cfg->endpoint) ?></td>
                <td><?= Html::encode($cfg->region) ?></td>
                <td><?= $cfg->is_default ? 'Да' : 'Нет' ?></td>
                <td>
                    <?= Html::a('Редактировать', ['update', 'id' => $cfg->id], ['class' => 'btn btn-primary btn-sm']) ?>
                    <?= Html::a('Удалить', ['delete', 'id' => $cfg->id], [
                        'class' => 'btn btn-danger btn-sm',
                        'data' => ['confirm' => 'Точно удалить?']
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
