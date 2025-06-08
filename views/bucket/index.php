<?php
use yii\bootstrap5\Html;

$this->title = 'Buckets';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bucket-index container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать bucket', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Имя</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($buckets as $b): ?>
            <tr>
                <td><?= Html::encode($b['Name']) ?></td>
                <td><?= Html::encode(date('Y-m-d H:i:s', strtotime($b['CreationDate']))) ?></td>
                <td>
                    <?= Html::a('Удалить', ['delete', 'name' => $b['Name']], [
                        'class' => 'btn btn-danger btn-sm',
                        'data' => [
                            'method'  => 'post',
                            'confirm' => 'Вы уверены, что хотите удалить этот bucket?',
                        ],
                    ]) ?>
                    <?= Html::a('Открыть', ['object/browse', 'bucket' => $b['Name']], [
                        'class' => 'btn btn-secondary btn-sm',
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
