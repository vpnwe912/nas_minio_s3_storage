<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

class MinioGroupController extends Controller
{
    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>AccessControl::class,
                'only'=>['index','create','update','delete','add-user','remove-user','enable','disable'],
                'rules'=>[
                    ['actions'=>['index'],       'allow'=>true,'roles'=>['@']],
                    ['actions'=>['create'],      'allow'=>true,'roles'=>['@']],
                    ['actions'=>['update'],      'allow'=>true,'roles'=>['@']],
                    ['actions'=>['delete'],      'allow'=>true,'roles'=>['@']],
                    ['actions'=>['add-user'],    'allow'=>true,'roles'=>['@']],
                    ['actions'=>['remove-user'], 'allow'=>true,'roles'=>['@']],
                    ['actions'=>['enable','disable'], 'allow'=>true,'roles'=>['@']],
                ],
                'denyCallback'=>fn() => throw new ForbiddenHttpException('У вас нет прав'),
            ],
        ];
    }

    // список групп
    public function actionIndex()
    {
        $groups = Yii::$app->minioAdmin->listGroups();
        return $this->render('index', ['groups' => $groups]);
    }

    // создание группы
    public function actionCreate()
    {
        // список всех пользователей и всех политик
        $allUsers    = ArrayHelper::getColumn(Yii::$app->minioAdmin->listUsers(), 'user');
        $allPolicies = ArrayHelper::getColumn(Yii::$app->minioAdmin->listPolicies(), 'policy');
    
        // модель с group, users[], policies[]
        $model = new DynamicModel(['group','users','policies']);
        $model->addRule('group','required')
              ->addRule('group','match',[
                 'pattern'=>'/^[A-Za-z0-9\-]+$/',
                 'message'=>'Только латинские буквы, цифры и дефис',
              ])
              ->addRule('users','each',['rule'=>['in','range'=>$allUsers]])
              ->addRule('policies','each',['rule'=>['in','range'=>$allPolicies]]);
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // создаём группу и сразу назначаем выбранные политики и пользователей
            if (!Yii::$app->minioAdmin->createGroup($model->group, $model->users)) {
                Yii::$app->session->setFlash('error','Не удалось создать группу');
                return $this->refresh();
            }
            // политики
            foreach ($model->policies as $pol) {
                Yii::$app->minioAdmin->setGroupPolicy($model->group, $pol);
            }
            Yii::$app->session->setFlash('success',"Группа «{$model->group}» создана");
            return $this->redirect(['index']);
        }
    
        return $this->render('create', [
            'model'       => $model,
            'allUsers'    => $allUsers,
            'allPolicies' => $allPolicies,
        ]);
    }
    
    

    // удаление группы
    public function actionDelete(string $group)
    {
        if (Yii::$app->minioAdmin->deleteGroup($group)) {
            Yii::$app->session->setFlash('success','Группа удалена');
        } else {
            Yii::$app->session->setFlash('error','Ошибка удаления группы');
        }
        return $this->redirect(['index']);
    }

    // добавить пользователя в группу
    public function actionAddUser(string $group)
    {
        $model = new DynamicModel(['user']);
        $model->addRule('user','required');
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if (Yii::$app->minioAdmin->addUserToGroup($group, $model->user)) {
                Yii::$app->session->setFlash('success',"Пользователь {$model->user} добавлен в группу");
            } else {
                Yii::$app->session->setFlash('error',"Не удалось добавить {$model->user}");
            }
        }
        return $this->redirect(['index']);
    }

    // удалить пользователя из группы
    public function actionRemoveUser(string $group, string $user)
    {
        if (Yii::$app->minioAdmin->removeUserFromGroup($group, $user)) {
            Yii::$app->session->setFlash('success',"Пользователь {$user} удалён из группы");
        } else {
            Yii::$app->session->setFlash('error',"Не удалось удалить {$user}");
        }
        return $this->redirect(['index']);
    }

    public function actionUpdate(string $group)
    {
        /** @var \app\components\MinioAdminService $service */
        $service      = Yii::$app->minioAdmin;
        $allUsers     = ArrayHelper::getColumn($service->listUsers(),    'user');
        $allPolicies  = ArrayHelper::getColumn($service->listPolicies(), 'policy');
    
        // получаем текущее состояние группы
        $info            = $service->getGroupInfo($group);
        $currentUsers    = $info['members'];    // всегда массив
        $currentPolicies = $info['policies'];   // всегда массив
    
        // динамическая модель
        $model = new DynamicModel(['users','policies']);
        $model->addRule('users',    'each', ['rule'=>['in','range'=>$allUsers]])
              ->addRule('policies', 'each', ['rule'=>['in','range'=>$allPolicies]]);
        $model->users    = $currentUsers;
        $model->policies = $currentPolicies;
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // приводим к массивам, чтобы array_diff не получал строку
            $newUsers    = is_array($model->users)    ? $model->users    : [];
            $newPolicies = is_array($model->policies) ? $model->policies : [];
        
            // --- обновляем участников ---
            $toAddUser    = array_diff($newUsers,     $currentUsers);
            $toRemoveUser = array_diff($currentUsers, $newUsers);
            foreach ($toAddUser    as $u) { $service->addUserToGroup   ($group, $u); }
            foreach ($toRemoveUser as $u) { $service->removeUserFromGroup($group, $u); }
        
            // --- обновляем политики ---
            $toAddPol    = array_diff($newPolicies,     $currentPolicies);
            $toRemovePol = array_diff($currentPolicies,  $newPolicies);
            foreach ($toAddPol    as $p) { $service->setGroupPolicy   ($group, $p); }
            foreach ($toRemovePol as $p) { $service->removeGroupPolicy($group, $p); }
        
            Yii::$app->session->setFlash('success', "Группа «{$group}» обновлена");
            return $this->redirect(['index']);
        }

    
        return $this->render('update', [
            'group'       => $group,
            'model'       => $model,
            'allUsers'    => $allUsers,
            'allPolicies' => $allPolicies,
        ]);
    }
    
    /**
     * Отключить группу (делает статус disabled)
     */
    public function actionDisable(string $group)
    {
        if (Yii::$app->minioAdmin->disableGroup($group)) {
            Yii::$app->session->setFlash('success',"Группа «{$group}» отключена");
        } else {
            Yii::$app->session->setFlash('error',"Не удалось отключить группу «{$group}»");
        }
        return $this->redirect(['index']);
    }

    /**
     * Включить группу (делает статус enabled)
     */
    public function actionEnable(string $group)
    {
        if (Yii::$app->minioAdmin->enableGroup($group)) {
            Yii::$app->session->setFlash('success',"Группа «{$group}» включена");
        } else {
            Yii::$app->session->setFlash('error',"Не удалось включить группу «{$group}»");
        }
        return $this->redirect(['index']);
    }
}
