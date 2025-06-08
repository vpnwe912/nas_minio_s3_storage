<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property string $name
 *
 * @property Group[] $groups
 */
class Permission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%permission}}';
    }

    public function rules()
    {
        return [
            ['name','required'],
            ['name','string','max'=>255],
            ['name','unique'],
        ];
    }

    /** Связь группы–право */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id'=>'group_id'])
            ->viaTable('{{%group_permission}}',['permission_id'=>'id']);
    }
}
