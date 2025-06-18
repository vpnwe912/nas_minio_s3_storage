<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $filename
 * @property string $version
 * @property string $type
 * @property string $path
 * @property int $size
 * @property string $hash
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $description
 */
class Binary extends ActiveRecord
{
    public static function tableName()
    {
        return 'binaries';
    }
}
