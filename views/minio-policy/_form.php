<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\PolicyForm */
/* @var $form yii\widgets\ActiveForm */
/* @var $buckets array */
/* @var $actions array */

$js = <<<JS
// Добавление нового statement
$('#add-statement').on('click', function(e) {
    e.preventDefault();
    var container = $('#statements-container');
    var index = container.find('.statement-item').length;
    var newItem = $('#statement-template').html()
        .replace(/__index__/g, index)
        .replace(/__comment__/g, '')
        .replace(/__bucket__/g, '')
        .replace(/__prefix__/g, '')
        .replace(/__actions__/g, '');
    
    container.append(newItem);
    container.find('.select2').select2();
    updateStatementIndexes();
});

// Удаление statement
$(document).on('click', '.remove-statement', function(e) {
    e.preventDefault();
    if ($('.statement-item').length > 1) {
        $(this).closest('.statement-item').remove();
        updateStatementIndexes();
    } else {
        alert('Должен остаться хотя бы один statement');
    }
});

// Обновление индексов полей
function updateStatementIndexes() {
    $('.statement-item').each(function(index) {
        $(this).find('input, select, textarea').each(function() {
            var name = $(this).attr('name');
            if (name) {
                name = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', name);
            }
            
            var id = $(this).attr('id');
            if (id) {
                id = id.replace(/\d+/, index);
                $(this).attr('id', id);
                $(this).siblings('label').attr('for', id);
            }
        });
    });
}

// Инициализация select2
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
JS;

$this->registerJs($js);
?>

<div class="minio-policy-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card">
        <div class="card-header">
            <h4>Основные настройки</h4>
        </div>
        <div class="card-body">
            <?= $form->field($model, 'name')->textInput([
                'maxlength' => true,
                'readonly' => false
            ]) ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Statements</h4>
            <button type="button" id="add-statement" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Добавить statement
            </button>
        </div>
        <div class="card-body">
            <div id="statements-container">
                <?php if (empty($model->statements)): ?>
                    <div class="statement-item card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">

                                <div class="col-md-6">
                                    <?= $form->field($model, 'statements[0][comment]')->textInput([
                                        'maxlength' => true,
                                        'placeholder' => 'Комментарий'
                                    ]) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'statements[0][bucket]')->dropDownList(
                                        $buckets,
                                        ['prompt' => 'Выберите бакет', 'class' => 'form-control']
                                    ) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'statements[0][prefix]')->textInput([
                                        'maxlength' => true,
                                        'placeholder' => 'Префикс (необязательно)'
                                    ]) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <?= $form->field($model, 'statements[0][actions][]')->checkboxList($actions, [
                                        'class' => 'checkbox-list',
                                        'item' => function($index, $label, $name, $checked, $value) {
                                            $options = [
                                                'label' => $label,
                                                'value' => $value,
                                                'labelOptions' => ['class' => 'checkbox-inline mr-3'],
                                            ];
                                            return '<div class="form-check form-check-inline">' . 
                                                Html::checkbox($name, $checked, $options) . 
                                                '</div>';
                                        }
                                    ]) ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-statement">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($model->statements as $i => $statement): ?>
                        <div class="statement-item card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">

                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($model, "statements[{$i}][comment]")->textInput([
                                            'value' => $statement['comment'] ?? '',
                                            'maxlength' => true,
                                            'placeholder' => 'Комментарий'
                                        ]) ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= $form->field($model, "statements[{$i}][bucket]")->dropDownList(
                                            $buckets,
                                            [
                                                'value' => $statement['bucket'] ?? '',
                                                'prompt' => 'Выберите бакет',
                                                'class' => 'form-control select2'
                                            ]
                                        ) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($model, "statements[{$i}][prefix]")->textInput([
                                            'value' => $statement['prefix'] ?? '',
                                            'maxlength' => true,
                                            'placeholder' => 'Префикс (необязательно)'
                                        ]) ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <?= $form->field($model, "statements[{$i}][actions][]")->checkboxList($actions, [
                                            'value' => $statement['actions'] ?? [],
                                            'class' => 'checkbox-list',
                                            'item' => function($index, $label, $name, $checked, $value) use ($statement) {
                                                $options = [
                                                    'label' => $label,
                                                    'value' => $value,
                                                    'checked' => in_array($value, $statement['actions'] ?? []),
                                                    'labelOptions' => ['class' => 'checkbox-inline mr-3'],
                                                ];
                                                return '<div class="form-check form-check-inline">' . 
                                                    Html::checkbox($name, $checked, $options) . 
                                                    '</div>';
                                            }
                                        ]) ?>
                                    </div>
                                </div>
                                <?php if ($i > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm remove-statement">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group mt-4">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Шаблон для нового statement -->
<template id="statement-template">
    <div class="statement-item card mb-3">
        <div class="card-body">
            <div class="row">

                <div class="col-md-6">
                    <div class="form-group field-policyform-statements-__index__-comment">
                        <label class="control-label" for="policyform-statements-__index__-comment">Comment</label>
                        <input type="text" id="policyform-statements-__index__-comment" 
                               class="form-control" name="PolicyForm[statements][__index__][comment]" 
                               maxlength="255" placeholder="Комментарий" value="__comment__">
                        <div class="help-block"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group field-policyform-statements-__index__-bucket">
                        <label class="control-label" for="policyform-statements-__index__-bucket">Bucket</label>
                        <select id="policyform-statements-__index__-bucket" 
                                class="form-control select2" name="PolicyForm[statements][__index__][bucket]">
                            <option value="">Выберите бакет</option>
                            <?php foreach ($buckets as $value => $label): ?>
                                <option value="<?= Html::encode($value) ?>" 
                                        <?= ($value === '__bucket__') ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group field-policyform-statements-__index__-prefix">
                        <label class="control-label" for="policyform-statements-__index__-prefix">Prefix</label>
                        <input type="text" id="policyform-statements-__index__-prefix" 
                               class="form-control" name="PolicyForm[statements][__index__][prefix]" 
                               maxlength="255" placeholder="Префикс (необязательно)" value="__prefix__">
                        <div class="help-block"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group field-policyform-statements-__index__-actions">
                        <label class="control-label">Actions</label>
                        <div class="checkbox-list">
                            <?php foreach ($actions as $value => $label): ?>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="policyform-statements-__index__-actions-<?= md5($value) ?>" 
                                           class="form-check-input" name="PolicyForm[statements][__index__][actions][]" 
                                           value="<?= Html::encode($value) ?>" 
                                           <?= (strpos('__actions__', $value) !== false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="policyform-statements-__index__-actions-<?= md5($value) ?>">
                                        <?= Html::encode($label) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="help-block"></div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-statement">
                <i class="fas fa-trash"></i> Удалить
            </button>
        </div>
    </div>
</template>