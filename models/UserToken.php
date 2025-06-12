<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $token
 * @property int    $created_at
 */
class UserToken extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_token}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'token', 'created_at'], 'required'],
            [['user_id', 'created_at'], 'integer'],
            [['token'], 'string', 'max' => 255],
            [['token'], 'unique'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
