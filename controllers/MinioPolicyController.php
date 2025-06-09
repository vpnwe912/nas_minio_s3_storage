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

    /** Создание новой политики */
    /** Создать политику */
    public function actionCreate()
    {
        $admin = Yii::$app->minioAdmin;
        $model = new PolicyForm();
    
        // Подставляем сразу один пустой Statement, чтобы в форме
        // при загрузке был хотя бы один блок
        $model->statements = [
            [
                'sid'      => '',
                'comment'  => '',
                'bucket'   => '',
                'prefix'   => '',
                'actions'  => [],
            ]
        ];
    
        if ($model->load(Yii::$app->request->post())) {
            $postStmts = Yii::$app->request->post('PolicyForm', [])['statements'] ?? [];
            // конвертируем в AWS-формат
            $awsStmts = $this->generateStatements($postStmts);
    
            if ($admin->createPolicy($model->name, $awsStmts)) {
                Yii::$app->session->setFlash('success', 'Политика создана');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', 'Не удалось создать политику');
        }
    
        return $this->render('create', [
            'model'      => $model,
            'allBuckets' => $this->getAllBuckets(),
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

    protected function extractPrefix($st)
    {
        if (isset($st['Condition']['StringLike']['s3:prefix'])) {
            return $st['Condition']['StringLike']['s3:prefix'];
        }
        if (preg_match('#^arn:aws:s3:::[^/]+/(.*)$#', $st['Resource'], $m)) {
            return $m[1];
        }
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


    /** Редактирование */
    /** Редактирование / обновление */
    public function actionUpdate(string $name)
    {
        $admin = Yii::$app->minioAdmin;

        // 1) Забираем JSON-политику
        $policyBody = $admin->getPolicyBody($name);
        if ($policyBody === null) {
            Yii::$app->session->setFlash('error', 'Не удалось загрузить политику');
            return $this->redirect(['index']);
        }

        // 2) Извлекаем Statement[]
        $rawStmts = $policyBody['Statement'] ?? [];

        // 3) Преобразуем в формат формы
        $formStmts = [];
        foreach ($rawStmts as $st) {
            // 3.1) Sid, Comment
            $sid     = $st['Sid']     ?? '';
            $comment = $st['Comment'] ?? '';

            // 3.2) Action — это массив строк вроде "s3:GetObject"
            $actions = (array)($st['Action'] ?? []);
            // убираем префикс "s3:"
            $plain   = array_map(function($a){
                return preg_replace('/^[^:]+:/', '', $a);
            }, $actions);

            // 3.3) Resource -> bucket + prefix
            $bucket = $prefix = '';
            if (!empty($st['Resource'][0])) {
                $res = $st['Resource'][0];  // "arn:aws:s3:::bucket/pref/*"
                $parts = explode(':::', $res, 2);
                $body  = $parts[1] ?? '';
                $bucket = strtok($body, '/');
                $prefix = substr($body, strlen($bucket) + 1);
                $prefix = rtrim($prefix, '*');
            }

            $formStmts[] = [
                'sid'      => $sid,
                'comment'  => $comment,
                'bucket'   => $bucket,
                'prefix'   => $prefix,
                'actions'  => $plain,      // теперь это массив ["GetObject",...]
            ];
        }

        // 4) Заполняем модель
        $model = new PolicyForm();
        $model->name       = $name;
        $model->statements = $formStmts;

        // 5) Обработка сохранения
        if ($model->load(Yii::$app->request->post())) {
            $post  = Yii::$app->request->post('PolicyForm', []);
            $newSt = $post['statements'] ?? [];

            // 5.1) Собираем AWS-формат Statement[]
            $awsStmts = [];
            foreach ($newSt as $ns) {
                $awsStmt = [
                    'Sid'     => $ns['sid'] ?? '',
                    'Effect'  => 'Allow',
                    // префиксируем
                    'Action'  => array_map(function($a){
                        return 's3:'.$a;
                    }, (array)($ns['actions'] ?? [])),
                    // строим ресурс
                    'Resource'=> [
                        "arn:aws:s3:::{$ns['bucket']}/{$ns['prefix']}*"
                    ],
                ];
                $awsStmts[] = $awsStmt;
            }

            if ($admin->putPolicy($model->name, $awsStmts)) {
                Yii::$app->session->setFlash('success', 'Политика обновлена');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', 'Ошибка при сохранении');
        }

        // 6) Рендер
        return $this->render('update', [
            'model'      => $model,
            'allBuckets' => $this->getAllBuckets(),
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
