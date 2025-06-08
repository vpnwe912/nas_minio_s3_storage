<?php
use yii\bootstrap5\Html;

$this->title = 'MinIO Пользователи';
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <p><?= Html::a('Создать пользователя',['create'],['class'=>'btn btn-success']) ?></p>
    <table class="table">
        <tr><th>User</th><th>Policies</th><th>Действия</th></tr>
        <?php foreach($users as $u): ?>
            <tr>
                <td><?= Html::encode($u['user']) ?></td>
                <td><?= Html::encode($u['policies']) ?></td>
                <td>
                    <?= Html::a('Сменить секрет', ['update','user'=>$u['user']], [
                        'class'=>'btn btn-sm btn-primary',
                    ]) ?>
                    <?= Html::a('Удалить', ['delete','user'=>$u['user']], [
                        'class'=>'btn btn-sm btn-danger',
                        'data'=>['method'=>'post','confirm'=>'Удалить пользователя?'],
                    ]) ?>
                    <?= Html::a('Политика', ['set-policy','user'=>$u['user']], [
                        'class'=>'btn btn-sm btn-secondary',
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
