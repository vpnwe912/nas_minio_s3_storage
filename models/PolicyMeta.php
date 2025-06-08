<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Модель для хранения комментариев к Sid в политиках MinIO.
 *
 * Таблица: policy_meta
 * Поля:
 * - policy_name (string, PK)
 * - sid         (string, PK)
 * - comment     (text, nullable)
 */
class PolicyMeta extends ActiveRecord
{
    /**
     * Имя таблицы в БД
     */
    public static function tableName()
    {
        return '{{%policy_meta}}';
    }

    /**
     * Правила валидации
     */
    public function rules()
    {
        return [
            [['policy_name', 'sid'], 'required'],
            ['policy_name', 'string', 'max' => 255],
            ['sid',         'string', 'max' => 255],
            ['comment',     'string'],
        ];
    }

    /**
     * Человеческие подписи для полей
     */
    public function attributeLabels()
    {
        return [
            'policy_name' => 'Имя политики',
            'sid'         => 'SID заявления',
            'comment'     => 'Комментарий',
        ];
    }
}
