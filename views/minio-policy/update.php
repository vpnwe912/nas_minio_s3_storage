<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;


$this->title = "Edit Policy «{$name}»";
$this->params['breadcrumbs'][] = ['label'=>'MinIO Policies','url'=>['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container mt-4">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

        <p><strong>Policy Name:</strong> <?= Html::encode($name) ?></p>

        <?= $form->field($model, 'json')
                ->textarea(['rows'=>20])
                ->hint('Edit JSON policy')
        ?>

        <div class="d-grid">
            <?= Html::submitButton('Save', ['class'=>'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>
