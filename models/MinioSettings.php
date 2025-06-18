<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MinioSettings extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%minio_settings}}';
    }

    public static function getValue($key, $default = null)
    {
        $item = self::findOne(['key' => $key]);
        return $item ? $item->value : $default;
    }

    public static function setValue($key, $value)
    {
        $item = self::findOne(['key' => $key]);
        if (!$item) {
            $item = new self();
            $item->key = $key;
        }
        $item->value = $value;
        $item->save();
    }
}
