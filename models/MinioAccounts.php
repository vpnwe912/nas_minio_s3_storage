<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "minio_accounts".
 *
 * @property int $id
 * @property int $user_id
 * @property string $minio_access_key
 * @property string $minio_secret_key
 * @property string $created_at
 *
 * @property User $user
 */
class MinioAccounts extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'minio_accounts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'minio_access_key', 'minio_secret_key'], 'required'],
            [['user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['minio_access_key', 'minio_secret_key'], 'string', 'max' => 255],
            [['minio_access_key'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true,
                'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь (user_id)',
            'minio_access_key' => 'MinIO Access Key',
            'minio_secret_key' => 'MinIO Secret Key',
            'created_at' => 'Дата создания',
        ];
    }

    /**
     * Связь с пользователем сайта
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
