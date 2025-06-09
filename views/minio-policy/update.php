<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var $model app\models\PolicyForm */
/** @var $allBuckets array */

$this->title = 'Редактировать политику «'.$model->name.'»';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['readonly'=>true]) ?>

    <div id="statements">
        <!-- шаблон для JS -->
        <div class="statement-template d-none">
            <hr>
            <?= Html::textInput('DUMMY[sid]',     null, ['class'=>'form-control','placeholder'=>'Sid']) ?>
            <?= Html::textarea('DUMMY[comment]', null, ['class'=>'form-control','placeholder'=>'Комментарий']) ?>
            <?= Html::dropDownList('DUMMY[bucket]', null, array_combine($allBuckets,$allBuckets), ['class'=>'form-select']) ?>
            <?= Html::textInput('DUMMY[prefix]',  '',   ['class'=>'form-control','placeholder'=>'Префикс внутри бакета']) ?>
            <?= Html::checkboxList('DUMMY[actions][]', [], [
                    'GetObject'=>'Get','PutObject'=>'Put','ListBucket'=>'List'
                ], ['class'=>'form-check']
            ) ?>
            <button type="button" class="btn btn-danger remove-stmt">Удалить</button>
        </div>

        <!-- уже существующие -->
        <?php foreach ($model->statements as $i => $stmt): ?>
        <div class="statement">
            <hr>
            <?= Html::textInput("PolicyForm[statements][$i][sid]",     $stmt['sid'],     ['class'=>'form-control']) ?>
            <?= Html::textarea("PolicyForm[statements][$i][comment]", $stmt['comment'], ['class'=>'form-control']) ?>
            <?= Html::dropDownList("PolicyForm[statements][$i][bucket]",
                   $stmt['bucket'], array_combine($allBuckets,$allBuckets),
                   ['class'=>'form-select']) ?>
            <?= Html::textInput("PolicyForm[statements][$i][prefix]",
                   $stmt['prefix'], ['class'=>'form-control']) ?>
            <?= Html::checkboxList("PolicyForm[statements][$i][actions]",
                   $stmt['actions'],           // уже массив plain-имен
                   ['GetObject'=>'Get','PutObject'=>'Put','ListBucket'=>'List'],
                   ['class'=>'form-check']
            ) ?>
            <button type="button" class="btn btn-danger remove-stmt">Удалить</button>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="button" id="add-stmt" class="btn btn-secondary mt-2">Добавить Statement</button>
    <div class="mt-3"><?= Html::submitButton('Сохранить', ['class'=>'btn btn-primary']) ?></div>

<?php ActiveForm::end(); ?>

<?php
// JS-код для динамического добавления/удаления блоков
$this->registerJs(<<<'JS'
    var container = document.getElementById('statements');
    document.getElementById('add-stmt').onclick = function(){
        var tmpl  = document.querySelector('.statement-template'),
            clone = tmpl.cloneNode(true),
            idx   = container.querySelectorAll('.statement').length;
        clone.classList.remove('d-none','statement-template');
        clone.classList.add('statement');
        clone.innerHTML = clone.innerHTML.replace(/DUMMY/g,'PolicyForm[statements]['+idx+']');
        container.append(clone);
    };
    container.addEventListener('click', function(e){
        if (e.target.matches('.remove-stmt')) {
            e.target.closest('.statement').remove();
        }
    });
JS
);
