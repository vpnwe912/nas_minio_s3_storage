<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\PolicyForm */

$this->title = 'Просмотр политики: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Политики MinIO', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="minio-policy-view">
    <div class="card">
        <div class="card-header">
            <h1><?= Html::encode($this->title) ?></h1>
            <div class="float-right">
                <?= Html::a('Редактировать', ['update', 'name' => $model->name], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Удалить', ['delete', 'name' => $model->name], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Вы уверены, что хотите удалить эту политику?',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'name',
                    'description:ntext',
                    // Дополнительные атрибуты, если есть
                ],
            ]) ?>

            <h3>Statements</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>SID</th>
                            <th>Bucket</th>
                            <th>Prefix</th>
                            <th>Actions</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->statements as $stmt): ?>
                            <tr>
                                <td><?= Html::encode($stmt['sid'] ?? '') ?></td>
                                <td><?= Html::encode($stmt['bucket'] ?? '') ?></td>
                                <td><?= Html::encode($stmt['prefix'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($stmt['actions'])): ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ((array)$stmt['actions'] as $action): ?>
                                                <li><?= Html::encode($action) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td><?= nl2br(Html::encode($stmt['comment'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>