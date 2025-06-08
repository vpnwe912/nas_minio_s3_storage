<?php
namespace app\controllers;

use Yii;
use app\models\User;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class'   => AccessControl::class,
                'only'    => ['index','create','update','delete'],
                'rules'   => [
                    // просмотр списка
                    [
                        'actions'      => ['index'],
                        'allow'        => true,
                        'roles'        => ['@'],
                        'matchCallback'=> fn() => Yii::$app->user->identity->hasPermission('viewUsers'),
                    ],
                    // создание
                    [
                        'actions'      => ['create'],
                        'allow'        => true,
                        'roles'        => ['@'],
                        'matchCallback'=> fn() => Yii::$app->user->identity->hasPermission('createUsers'),
                    ],
                    // редактирование
                    [
                        'actions'      => ['update'],
                        'allow'        => true,
                        'roles'        => ['@'],
                        'matchCallback'=> fn() => Yii::$app->user->identity->hasPermission('updateUsers'),
                    ],
                    // удаление
                    [
                        'actions'      => ['delete'],
                        'allow'        => true,
                        'roles'        => ['@'],
                        'matchCallback'=> fn() => Yii::$app->user->identity->hasPermission('deleteUsers'),
                    ],
                ],
                // по умолчанию — отказ
                'denyCallback' => fn() => $this->redirect(['site/error', 'message'=>'Нет прав']),
            ],
        ];
    }
    

    /** Список пользователей */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
            'pagination' => ['pageSize' => 20],
            'sort'=> ['defaultOrder'=> ['id'=>SORT_DESC]],
        ]);
        return $this->render('index', ['dataProvider'=>$dataProvider]);
    }

    /** Добавление */
    public function actionCreate()
    {
        $model = new User(['scenario'=>'create']);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        // список групп для выпадашки
        $groups = Yii::$app->db
            ->createCommand('SELECT id, name FROM {{%group}}')
            ->queryAll();
        $groupsList = \yii\helpers\ArrayHelper::map($groups,'id','name');

        return $this->render('create', [
            'model'      => $model,
            'groupsList' => $groupsList,
        ]);
    }

    /** Редактирование */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'update';
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        $groups = Yii::$app->db
            ->createCommand('SELECT id, name FROM {{%group}}')
            ->queryAll();
        $groupsList = \yii\helpers\ArrayHelper::map($groups,'id','name');

        return $this->render('update', [
            'model'      => $model,
            'groupsList' => $groupsList,
        ]);
    }

    /** Удаление */
    public function actionDelete($id)
    {
        if ($id == 1) {
            throw new \yii\web\ForbiddenHttpException('Нельзя удалить главного администратора.');
        }
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /** Поиск модели или 404 */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Пользователь не найден.');
    }
}
