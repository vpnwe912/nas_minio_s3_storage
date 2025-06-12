<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="policy-form box box-primary">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'comment')->textarea(['maxlength' => true, 'rows'=>2]) ?>
    <?= $form->field($model, 'bucket')->dropDownList($buckets) ?>

    <div id="folders-list">
        <?php foreach ($model->folders as $i => $folder): ?>
            <div class="row folder-row">
                <div class="col-md-4">
                    <?= Html::activeTextInput($model, "folders[$i]", [
                        'class' => 'form-control',
                        'placeholder' => 'Папка (prefix)'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Html::checkboxList("PolicyForm[actions][$i]", $model->actions[$i] ?? [], $actions) ?>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-remove-folder">-</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-primary" id="add-folder">+ Добавить папку</button>

    <div class="form-group" style="margin-top: 20px;">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
$('#add-folder').on('click', function(){
    let idx = $('#folders-list .folder-row').length;
    let html = `<div class="row folder-row">
        <div class="col-md-4">
            <input class="form-control" name="PolicyForm[folders][`+idx+`]" placeholder="Папка (prefix)" />
        </div>
        <div class="col-md-6">
            <label><input type="checkbox" name="PolicyForm[actions][`+idx+`][]" value="s3:GetObject"> Чтение</label>
            <label><input type="checkbox" name="PolicyForm[actions][`+idx+`][]" value="s3:PutObject"> Запись</label>
            <label><input type="checkbox" name="PolicyForm[actions][`+idx+`][]" value="s3:DeleteObject"> Удаление</label>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-remove-folder">-</button>
        </div>
    </div>`;
    $('#folders-list').append(html);
});
$(document).on('click', '.btn-remove-folder', function(){
    $(this).closest('.folder-row').remove();
});
JS;
$this->registerJs($js);
?>
