<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $provider
 * @property string $endpoint
 * @property string $region
 * @property string|null $location_constraint
 * @property string|null $comment
 * @property int $is_default
 * @property string $created_at
 */
class MinioConfigs extends ActiveRecord
{
    public static function tableName()
    {
        return 'minio_configs';
    }

    public function rules()
    {
        return [
            [['name', 'provider', 'endpoint', 'region'], 'required'],
            [['is_default'], 'boolean'],
            [['created_at'], 'safe'],
            [['name', 'provider', 'endpoint', 'region', 'location_constraint'], 'string', 'max' => 255],
            [['comment'], 'string'],
            [['name'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'provider' => 'Провайдер',
            'endpoint' => 'Endpoint',
            'region' => 'Регион',
            'location_constraint' => 'Location Constraint',
            'comment' => 'Комментарий',
            'is_default' => 'По умолчанию',
            'created_at' => 'Дата создания',
        ];
    }
}
