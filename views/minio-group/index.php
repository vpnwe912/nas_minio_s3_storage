<?php
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $groups array [['group'=>'dev','users'=>'alice,bob'], …] */

$this->title = 'MinIO Группы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a('Создать группу', ['minio-group/create'], ['class'=>'btn btn-success']) ?>
    </p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Group</th>
                <th>Status</th>
                <th>Users</th>
                <th>Policies</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $g): ?>
    <tr>
        <td><?= Html::encode($g['group']) ?></td>
        <td><?= Html::encode($g['status']) ?></td>
        <td><?= Html::encode($g['users']) ?></td>
        <td><?= Html::encode($g['policies']) ?></td>
        <td>
            <?php if ($g['status'] === 'enabled'): ?>
                <?= Html::a('Отключить', ['disable','group'=>$g['group']], [
                    'class'=>'btn btn-sm btn-warning',
                    'data'=>['method'=>'post'],
                ]) ?>
            <?php else: ?>
                <?= Html::a('Включить', ['enable','group'=>$g['group']], [
                    'class'=>'btn btn-sm btn-success',
                    'data'=>['method'=>'post'],
                ]) ?>
            <?php endif ?>

            <?= Html::a('Изменить', ['update','group'=>$g['group']], ['class'=>'btn btn-sm btn-primary']) ?>
            <?= Html::a('Удалить', ['delete','group'=>$g['group']], [
                'class'=>'btn btn-sm btn-danger',
                'data'=>['confirm'=>'Удалить группу?','method'=>'post']
            ]) ?>
        </td>
    </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>
