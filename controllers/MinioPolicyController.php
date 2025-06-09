<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use app\models\PolicyForm;
use app\models\PolicyMeta;
use yii\helpers\ArrayHelper;

class MinioPolicyController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','update','delete'],
                'rules'=>[
                    ['actions'=>['index'],  'allow'=>true,'roles'=>['@']],
                    ['actions'=>['create'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['update'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['delete'], 'allow'=>true,'roles'=>['@']],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    /** Получить имена бакетов из MinioService */
    protected function getAllBuckets(): array
    {
        $svc = Yii::$app->minio;        // ваш MinioService
        $list = $svc->listBuckets();    // массив [ ['Name'=>'share'], ... ]
        $names = [];
        foreach ($list as $b) {
            $names[] = is_array($b) ? ($b['Name'] ?? '') : ($b->get('Name') ?? '');
        }
        return array_filter($names);
    }

    /** Список всех политик */
    public function actionIndex()
    {
        $policies = Yii::$app->minioAdmin->listPolicies();
    
        // Добавляем для каждой политики поле comments
        foreach ($policies as &$policy) {
            $name = $policy['policy']; // или другой ключ с именем
            $metas = PolicyMeta::find()->where(['policy_name' => $name])->all();
            $lines = [];
            foreach ($metas as $m) {
                $lines[] = $m->comment;
            }
            // Собираем в многострочную строку
            $policy['comments'] = implode("\n", $lines);
        }
        unset($policy);
    
        return $this->render('index', ['policies' => $policies]);
    }



    // удаление политики
    public function actionDelete(string $name)
    {
        if (Yii::$app->minioAdmin->deletePolicy($name)) {
            Yii::$app->session->setFlash('success','Политика удалена');
        } else {
            Yii::$app->session->setFlash('error','Ошибка удаления политики');
        }
        return $this->redirect(['index']);
    }

/**
 * Создание новой политики
 */
public function actionCreate()
{
    $admin = Yii::$app->minioAdmin;
    $model = new PolicyForm();

    // Из контроллера вы уже делаете:
    $model->statements = [
        ['sid'=>'','comment'=>'','bucket'=>'','prefix'=>'','actions'=>[]]
    ];

    if ($model->load(Yii::$app->request->post())) {
        // 1) Берём «сырые» данные из POST
        $post      = Yii::$app->request->post('PolicyForm', []);
        $stmtsPost = $post['statements'] ?? [];

        // 2) Оставляем только те, где выбраны хоть какие-то actions
        $stmts = array_filter(
            $stmtsPost,
            fn($s) => !empty($s['actions'])
        );

        // 3) Если остался хотя бы один блок — генерируем sid, если поле пустое
        foreach ($stmts as $i => &$s) {
            if (empty($s['sid'])) {
                // example: policyName_1, policyName_2 ...
                $s['sid'] = $model->name . '_' . ($i + 1);
            }
        }
        unset($s);

        // 4) Генерируем JSON-массив для MinIO
        $awsStmts = $this->generateStatements($stmts);

        if ($admin->createPolicy($model->name, $awsStmts)) {
            // 5) Сохраняем в БД только отфильтрованные записи
            foreach ($stmts as $s) {
                // Это теперь гарантированно уникальные sid
                $meta = new PolicyMeta();
                $meta->policy_name = $model->name;
                $meta->sid         = $s['sid'];
                $meta->comment     = $s['comment'] ?? '';
                $meta->save(false);
            }

            Yii::$app->session->setFlash('success', 'Политика создана');
            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash('error', 'Не удалось создать политику');
    }

    return $this->render('create', [
        'model'       => $model,
        'allBuckets'  => $this->getAllBuckets(),
        'actionsList' => Yii::$app->params['minioActions'],
    ]);
}
    

/**
 * Получает данные политики для формы (из файла)
 */
protected function loadPolicyForm($name)
{
    $model = new \app\models\PolicyForm();
    $model->name = $name;

    $policyFile = Yii::getAlias('@runtime/minio-policies/' . $name . '.json');
    if (file_exists($policyFile)) {
        $json = file_get_contents($policyFile);
        $data = json_decode($json, true);
        // statements должны быть массивом
        $model->statements = isset($data['Statement']) ? $data['Statement'] : [];
    } else {
        // Если файла нет — statements пустой массив
        $model->statements = [];
    }

    return $model;
}

protected function extractBucket($resource)
{
    // arn:aws:s3:::bucket или arn:aws:s3:::bucket/prefix
    if (preg_match('#^arn:aws:s3:::(.*?)(/.*?)?$#', $resource, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Извлекает префикс из Statement- блока
 *
 * @param array $st AWS-statement
 * @return string префикс (или '*' по умолчанию)
 */
protected function extractPrefix(array $st): string
{
    // 1) Если явно задано условие StringLike для s3:prefix
    if (
        isset($st['Condition']['StringLike']) &&
        isset($st['Condition']['StringLike']['s3:prefix'])
    ) {
        return $st['Condition']['StringLike']['s3:prefix'];
    }

    // 2) Берём ресурс — если это массив, достаём первый элемент
    $resource = $st['Resource'] ?? '';
    if (is_array($resource)) {
        // предполагается, что ARN вида "arn:aws:s3:::bucket/prefix"
        $resource = reset($resource);
    }

    // 3) Парсим из ARN префикс после слеша
    if (
        is_string($resource) &&
        preg_match('#^arn:aws:s3:::[^/]+/(.*)$#', $resource, $m)
    ) {
        return $m[1];
    }

    // 4) По умолчанию — весь бакет
    return '*';
}

    protected function extractAction($st)
    {
        if ($st['Action'] == 's3:ListBucket') return 'ListBucket';
        return str_replace('s3:', '', $st['Action']);
    }

    /**
     * Генерирует AWS-statement’ы из данных формы
     * @param array $statementsData
     * @return array
     */
    protected function generateStatements(array $statementsData): array
    {
        $statements = [];
        foreach ($statementsData as $st) {
            // префиксируем все выбранные действия
            $actions = array_map(fn($a) => 's3:'.$a, (array)($st['actions'] ?? []));
            $statements[] = [
                'Sid'      => $st['sid']     ?? '',
                'Effect'   => 'Allow',
                'Action'   => $actions,
                'Resource' => ["arn:aws:s3:::{$st['bucket']}/{$st['prefix']}*"],
            ];
        }
        return $statements;
    }


/**
 * Обновление существующей политики
 */
public function actionUpdate(string $name)
{
    $admin = Yii::$app->minioAdmin;

    // 1) Загружаем политику из MinIO
    $policyBody = $admin->getPolicyBody($name);
    if ($policyBody === null) {
        Yii::$app->session->setFlash('error', 'Не удалось загрузить политику');
        return $this->redirect(['index']);
    }

    // 2) Загружаем все комментарии из БД (sid => PolicyMeta)
    $metaMap = PolicyMeta::find()
        ->where(['policy_name' => $name])
        ->indexBy('sid')
        ->all();

    // 3) Формируем массив для формы, подставляя комментарий из БД
    $rawStmts  = $policyBody['Statement'] ?? [];
    $formStmts = [];
    foreach ($rawStmts as $st) {
        $sid = $st['Sid'] ?? '';
        $formStmts[] = [
            'sid'     => $sid,
            'comment' => isset($metaMap[$sid]) ? $metaMap[$sid]->comment : '',
            'bucket'  => $this->extractBucket(
                is_array($st['Resource']) ? reset($st['Resource']) : ($st['Resource'] ?? '')
            ),
            'prefix'  => $this->extractPrefix($st),
            'actions' => array_map(fn($a) => preg_replace('/^[^:]+:/','',$a),
                                  (array)($st['Action'] ?? [])),
        ];
    }

    // 4) Заполняем форму
    $model = new PolicyForm();
    $model->name       = $name;
    $model->statements = $formStmts;

    if ($model->load(Yii::$app->request->post())) {
        // 5) Получаем новые данные из формы
        $post = Yii::$app->request->post('PolicyForm', []);
        $postStmts = $post['statements'] ?? [];

        // 6) Автогенерация sid, если пустой
        foreach ($postStmts as $i => &$stmt) {
            if (empty($stmt['sid'])) {
                $stmt['sid'] = $model->name . '_' . ($i + 1);
            }
        }
        unset($stmt);

        // 7) Генерируем JSON и сохраняем в MinIO
        $awsStmts = $this->generateStatements($postStmts);
        if ($admin->putPolicy($model->name, $awsStmts)) {
            // 8) Синхронизируем БД: обновляем, создаём, удаляем
            $existing = PolicyMeta::find()
                ->where(['policy_name' => $model->name])
                ->indexBy('sid')
                ->all();

            foreach ($postStmts as $stmt) {
                $sid     = $stmt['sid'];
                $comment = $stmt['comment'] ?? '';

                if (isset($existing[$sid])) {
                    $existing[$sid]->comment = $comment;
                    $existing[$sid]->save(false);
                    unset($existing[$sid]);
                } else {
                    $meta = new PolicyMeta();
                    $meta->policy_name = $model->name;
                    $meta->sid         = $sid;
                    $meta->comment     = $comment;
                    $meta->save(false);
                }
            }
            // удаляем старые записи
            foreach ($existing as $toDelete) {
                $toDelete->delete();
            }

            Yii::$app->session->setFlash('success', 'Политика обновлена');
            return $this->redirect(['index']);
        }
        Yii::$app->session->setFlash('error', 'Ошибка при сохранении');
    }

    return $this->render('update', [
        'model'       => $model,
        'allBuckets'  => $this->getAllBuckets(),
        'actionsList' => Yii::$app->params['minioActions'],
    ]);

}

    
    



    


// Преобразует minio policy json в statements для формы
protected function extractStatements($policyJson)
{
    $data = json_decode($policyJson, true);
    return $data['Statement'] ?? [];
}

// Собирает JSON политики из формы (name, statements)
protected function generatePolicyJson($name, $statements)
{
    // Пример простого генератора
    return json_encode([
        'Version' => '2012-10-17',
        'Statement' => $statements,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}


    
    

}
