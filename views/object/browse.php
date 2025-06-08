<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = "Bucket: {$bucket}" . ($prefix ? " / {$prefix}" : '');
$this->params['breadcrumbs'][] = ['label'=>'Buckets','url'=>['bucket/index']];
$this->params['breadcrumbs'][] = $bucket;
if ($prefix) {
    $crumbs = explode('/', trim($prefix,'/'));
    $acc = '';
    foreach ($crumbs as $c) {
        $acc .= $c . '/';
        $this->params['breadcrumbs'][] = [
            'label' => $c,
            'url'   => ['browse','bucket'=>$bucket,'prefix'=>$acc],
        ];
    }
}
?>
<div class="object-browse container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('← Назад к списку buckets',['bucket/index'],['class'=>'btn btn-light']) ?>
    </p>

    <h4>Папки</h4>
    <ul>
    <?php foreach ($folders as $f): ?>
        <?php $name = rtrim(basename($f['Prefix']),'/'); ?>
        <li>
            <?= Html::a("[{$name}]", ['browse','bucket'=>$bucket,'prefix'=>$f['Prefix']]) ?>
            <?= Html::a('X',['delete','bucket'=>$bucket,'key'=>$f['Prefix'],'prefix'=>$prefix],[
                'class'=>'text-danger','data'=>['method'=>'post','confirm'=>'Удалить папку?'],
            ]) ?>
        </li>
    <?php endforeach; ?>
    </ul>

    <h4>Файлы</h4>
    <ul>
    <?php foreach ($objects as $o): ?>
        <?php if ($o['Key'] === $prefix) continue; ?>// пропускаем «папку» -->
        <li>
            <?= Html::encode(basename($o['Key'])) ?>
            (<?= round($o['Size']/1024,2) ?> KB)
            <?= Html::a('Скачать',['//'.$bucket.'.minio.consider.pp.ua/'.$o['Key']],['class'=>'btn btn-sm btn-outline-primary','target'=>'_blank']) ?>
            <?= Html::a('X',['delete','bucket'=>$bucket,'key'=>$o['Key'],'prefix'=>$prefix],[
                'class'=>'text-danger','data'=>['method'=>'post','confirm'=>'Удалить файл?'],
            ]) ?>
        </li>
    <?php endforeach; ?>
    </ul>

    <hr>

    <h4>Создать папку</h4>
    <?php $f = ActiveForm::begin([
        'action' => ['create-folder','bucket'=>$bucket,'prefix'=>$prefix],
    ]); ?>
        <?= $f->field($folderModel,'name')->textInput() ?>
        <?= Html::submitButton('Создать', ['class'=>'btn btn-primary']) ?>
    <?php ActiveForm::end(); ?>

    <hr>

    <h4>Загрузить файл(ы)</h4>
    <?php $u = ActiveForm::begin([
        'action'    => ['upload','bucket'=>$bucket,'prefix'=>$prefix],
        'options'   => ['enctype'=>'multipart/form-data'],
    ]); ?>
        <?= $u->field($uploadModel,'files[]')
               ->fileInput(['multiple'=>true]) ?>
        <?= Html::submitButton('Загрузить', ['class'=>'btn btn-primary']) ?>
    <?php ActiveForm::end(); ?>
</div>
