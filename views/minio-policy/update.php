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
            <?= Html::dropDownList('DUMMY[bucket]', null, array_combine($allBuckets,$allBuckets), ['class'=>'form-select']) ?>
            <?= Html::textInput('DUMMY[prefix]',  '',   ['class'=>'form-control','placeholder'=>'Префикс внутри бакета']) ?>
            <?= Html::checkboxList(
                'DUMMY[actions][]',
                [],
                $actionsList,
                ['class'=>'form-check']
            ) ?>
            <button type="button" class="btn btn-danger remove-stmt">Удалить</button>
        </div>

        <!-- уже существующие -->
        <?php foreach ($model->statements as $i => $stmt): ?>
    <div class="statement">
        <hr>
        <?php if ($i === 0): ?>
            <?= Html::textInput(
                   "PolicyForm[statements][$i][sid]",
                   $stmt['sid'],
                   ['class'=>'form-control','placeholder'=>'Sid']
               ) ?>
            <?= Html::textarea(
                   "PolicyForm[statements][$i][comment]",
                   $stmt['comment'],
                   ['class'=>'form-control','placeholder'=>'Комментарий']
               ) ?>
        <?php endif; ?>

        <?= Html::dropDownList(
               "PolicyForm[statements][$i][bucket]",
               $stmt['bucket'],
               array_combine($allBuckets,$allBuckets),
               ['class'=>'form-select']
           ) ?>

        <?= Html::textInput(
               "PolicyForm[statements][$i][prefix]",
               $stmt['prefix'],
               ['class'=>'form-control','placeholder'=>'Префикс внутри бакета']
           ) ?>

        <?= Html::checkboxList(
               "PolicyForm[statements][$i][actions]",
               $stmt['actions'] ?? [],
               $actionsList,
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
$this->registerJs(<<<'JS'
    const container = document.getElementById('statements');
    document.getElementById('add-stmt').addEventListener('click', function() {
        // клонируем шаблон
        const tmpl  = document.querySelector('.statement-template');
        const clone = tmpl.cloneNode(true);
        const idx   = container.querySelectorAll('.statement').length;

        // делаем его видимым и помечаем как statement
        clone.classList.remove('d-none','statement-template');
        clone.classList.add('statement');

        // перебираем все input/textarea/select внутри клона
        clone.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
            // старое имя, например "DUMMY[sid]" или "DUMMY[actions][]"
            const oldName = el.getAttribute('name');
            // новое имя: "PolicyForm[statements][<idx>][sid]" и т.п.
            const newName = oldName.replace(
                /^DUMMY/,
                'PolicyForm[statements][' + idx + ']'
            );
            el.setAttribute('name', newName);
        });

        container.appendChild(clone);
    });

    // кнопка «Удалить»
    container.addEventListener('click', e => {
        if (e.target.matches('.remove-stmt')) {
            e.target.closest('.statement').remove();
        }
    });
JS
);
?>
