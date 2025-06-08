<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * @property int    $id
 * @property string $name
 *
 * @property int[]       $permissionIds
 * @property Permission[] $permissions
 */
class Group extends ActiveRecord
{
    public $permissionIds = [];

    public static function tableName()
    {
        return '{{%group}}';
    }

    public function rules()
    {
        return [
            ['name','required'],
            ['name','string','max'=>255],
            ['name','unique'],
            ['permissionIds','each','rule'=>['integer']],
        ];
    }

    public function afterFind()
    {
        parent::afterFind();
        // заполняем массив прав
        $this->permissionIds = ArrayHelper::getColumn(
            $this->getPermissions()->asArray()->all(),
            'id'
        );
    }

    public function afterSave($insert,$attrs)
    {
        parent::afterSave($insert,$attrs);
        // удаляем старые связи
        Yii::$app->db
            ->createCommand()
            ->delete('{{%group_permission}}',['group_id'=>$this->id])
            ->execute();
        // вставляем новые
        foreach ($this->permissionIds as $pid) {
            Yii::$app->db
                ->createCommand()
                ->insert('{{%group_permission}}',[
                    'group_id'      => $this->id,
                    'permission_id' => $pid,
                ])->execute();
        }
    }

    /** Relation to Permission via pivot */
    public function getPermissions()
    {
        return $this->hasMany(Permission::class,['id'=>'permission_id'])
            ->viaTable('{{%group_permission}}',['group_id'=>'id']);
    }

    public function attributeLabels()
    {
        return ['permissionIds'=>'Права'];
    }
}
