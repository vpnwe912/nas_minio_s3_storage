<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\PolicyForm;
use app\models\PolicyMeta;
use app\components\MinioAdminService;

class MinioPolicyController extends Controller
{
    private $adminService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->adminService = new MinioAdminService();
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }


    /**
     * Список всех политик
     */
    public function actionIndex()
    {
        $systemPolicies = [
            'consoleAdmin',
            'writeonly',
            'readonly',
            'readwrite',
            'diagnostics'
        ];
        $policies = array_filter(
            $this->adminService->listPolicies(),
            function($name) use ($systemPolicies) {
                return !in_array($name, $systemPolicies);
            }
        );
        $comments = [];
    
        // Загружаем комментарии для всех политик
        if (!empty($policies)) {
            $metas = PolicyMeta::find()
                ->select(['policy_name', 'comment'])
                ->where(['policy_name' => $policies])
                ->groupBy(['policy_name', 'sid'])
                ->indexBy('policy_name')
                ->all();
            
            foreach ($metas as $policyName => $meta) {
                $comments[$policyName] = $meta->comment;
            }
        }
    
        return $this->render('index', [
            'policies' => $policies,
            'comments' => $comments
        ]);
    }

    /**
     * Создание новой политики
     */
    public function actionCreate()
    {
        $model = new PolicyForm();
        $model->folders = ['']; // первая строка
        $model->actions = [[]];
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $model->folders = array_values($model->folders);
            $model->actions = array_values($model->actions);
        
            if ((new MinioAdminService())->savePolicy($model->name, $model)) {
                var_dump($model->comment);
                \app\models\PolicyMeta::savePolicyComment($model->name, $model->comment);
                Yii::$app->session->setFlash('success', 'Политика успешно обновлена');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении политики');
        }
        return $this->render('create', [
            'model' => $model,
            'buckets' => $this->getBucketsList(),
            'actions' => $this->getAvailableActions()
        ]);
    }


    /**
     * Редактирование существующей политики
     */
    public function actionUpdate($name)
    {
        $policy = (new MinioAdminService())->getPolicy($name);
        if ($policy === null) {
            throw new NotFoundHttpException('Политика не найдена');
        }
    
        $model = new PolicyForm();
        $model->name = $name;
        $model->comment = \app\models\PolicyMeta::getPolicyComment($name);
    
        // === Новый разбор политики ===
    
// 1. Ищем bucket только из ListBucket statement
$model->bucket = '';
foreach ($policy['Statement'] as $stmt) {
    if (in_array('s3:ListBucket', (array)($stmt['Action'] ?? []))) {
        foreach ((array)($stmt['Resource'] ?? []) as $res) {
            if (preg_match('/arn:aws:s3:::([^\/]+)/', $res, $matches)) {
                $model->bucket = $matches[1];
                break 2;
            }
        }
    }
}

// 2. Собираем все prefix (папки)
$folders = [];
if ($model->bucket) {
    foreach ($policy['Statement'] as $stmt) {
        if (in_array('s3:ListBucket', (array)($stmt['Action'] ?? []))) {
            if (isset($stmt['Condition']['StringLike']['s3:prefix'])) {
                foreach ((array)$stmt['Condition']['StringLike']['s3:prefix'] as $prefix) {
                    $folders[] = rtrim($prefix, '/*');
                }
            }
        }
    }
    $folders = array_unique($folders);
}

// 3. Для каждой папки ищем права (actions)
$actions = [];
foreach ($folders as $i => $folder) {
    $acts = [];
    foreach ($policy['Statement'] as $stmt) {
        // Пропускаем ListBucket, он уже обработан
        if (in_array('s3:ListBucket', (array)($stmt['Action'] ?? []))) {
            continue;
        }
        foreach ((array)($stmt['Resource'] ?? []) as $res) {
            // Сравниваем папку с arn
            if ($folder === '') {
                // полный доступ
                $pattern = '/arn:aws:s3:::' . preg_quote($model->bucket, '/') . '\/\*$/';
            } else {
                $pattern = '/arn:aws:s3:::' . preg_quote($model->bucket, '/') . '\/' . preg_quote($folder, '/') . '\/\*$/';
            }
            if (preg_match($pattern, $res)) {
                foreach ((array)$stmt['Action'] as $action) {
                    if (!in_array($action, $acts)) {
                        $acts[] = $action;
                    }
                }
            }
        }
    }
    $actions[$i] = $acts;
}
$model->folders = $folders;
$model->actions = $actions;
    
        // === Конец разбора политики ===
    
        // Если форма отправлена
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            
            $model->folders = array_values($model->folders);
            $model->actions = array_values($model->actions);
    
            if ((new MinioAdminService())->savePolicy($model->name, $model)) {
                \app\models\PolicyMeta::savePolicyComment($model->name, $model->comment);
                Yii::$app->session->setFlash('success', 'Политика успешно обновлена');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении политики');
        }
    
        return $this->render('update', [
            'model' => $model,
            'buckets' => $this->getBucketsList(),
            'actions' => $this->getAvailableActions()
        ]);
    }
    


    
//     public function actionTestComment()
// {
//     $m = new \app\models\PolicyMeta();
//     $m->policy_name = 'testkey';
//     $m->sid = 'main';
//     $m->comment = 'Тест комментарий!';
//     $m->save(false);
//     var_dump($m->getAttributes());
//     die('ok');
// }
    

    /**
     * Удаление политики
     */
    public function actionDelete($name)
    {
        if ($this->adminService->deletePolicy($name)) {
            // Удаляем связанные комментарии
            PolicyMeta::deleteAll(['policy_name' => $name]);
            Yii::$app->session->setFlash('success', 'Политика удалена');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении политики');
        }

        return $this->redirect(['index']);
    }

    /**
     * Получение списка бакетов
     */
    private function getBucketsList()
    {
        $buckets = Yii::$app->minio->listBuckets();
        $bucketNames = array_column($buckets, 'Name');
        return array_combine($bucketNames, $bucketNames);
    }

    /**
     * Доступные действия для политик
     */
    private function getAvailableActions()
    {
        return [
            's3:GetObject' => 'Чтение объектов',
            's3:PutObject' => 'Запись объектов',
            's3:DeleteObject' => 'Удаление объектов',
        ];
    }

    /**
     * Подготовка statements для формы
     */
    private function prepareFormStatements(array $statements)
    {
        $result = [];
        
        foreach ($statements as $stmt) {
            $result[] = [
                'sid' => $stmt['Sid'] ?? '',
                'comment' => $this->getCommentForSid($stmt['Sid'] ?? ''),
                'bucket' => $this->extractBucket($stmt['Resource'] ?? ''),
                'prefix' => $this->extractPrefix($stmt),
                'actions' => $this->extractActions($stmt['Action'] ?? [])
            ];
        }
        
        return $result;
    }

    /**
     * Подготовка statements для сохранения
     */
    private function prepareStatements(array $formStatements)
    {
        $statements = [];
        
        foreach ($formStatements as $stmt) {
            $resources = [];
            $prefix = trim($stmt['prefix'] ?? '');
            
            if (!empty($prefix)) {
                $resources[] = "arn:aws:s3:::" . $stmt['bucket'] . "/" . $prefix . "/*";
            } else {
                $resources[] = "arn:aws:s3:::" . $stmt['bucket'] . "/*";
            }

            $statements[] = [
                'Sid' => $stmt['sid'] ?: uniqid('sid_'),
                'Effect' => 'Allow',
                'Action' => (array)($stmt['actions'] ?? []),
                'Resource' => $resources
            ];
        }
        
        return $statements;
    }

    /**
     * Извлечение бакета из ARN
     */
    private function extractBucket($resource)
    {
        if (is_array($resource)) {
            $resource = reset($resource);
        }

        if (preg_match('/arn:aws:s3:::([^\/]+)/', $resource, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Извлечение префикса из ARN
     */
    private function extractPrefix(array $statement)
    {
        if (isset($statement['Condition']['StringLike']['s3:prefix'][0])) {
            return rtrim($statement['Condition']['StringLike']['s3:prefix'][0], '*');
        }

        if (isset($statement['Resource'])) {
            $resources = (array)$statement['Resource'];
            foreach ($resources as $resource) {
                if (preg_match('/arn:aws:s3:::[^\/]+\/(.+?)(?:\*|$)/', $resource, $matches)) {
                    return $matches[1];
                }
            }
        }

        return '';
    }

    /**
     * Извлечение действий из statement
     */
    private function extractActions($actions)
    {
        $result = [];
        foreach ((array)$actions as $action) {
            $result[] = $action;
        }
        return $result;
    }

    /**
     * Получение комментария по SID
     */
    private function getCommentForSid($sid, $policyName = null)
    {
        if (empty($sid)) return '';

        $query = PolicyMeta::find()->where(['sid' => $sid]);
        if ($policyName) {
            $query->andWhere(['policy_name' => $policyName]);
        }

        $meta = $query->one();
        return $meta ? $meta->comment : '';
    }
}
