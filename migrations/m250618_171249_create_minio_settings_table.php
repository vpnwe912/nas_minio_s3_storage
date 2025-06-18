<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%minio_settings}}`.
 */
class m250618_171249_create_minio_settings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%minio_settings}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string(128)->notNull()->unique(),
            'value' => $this->text(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%minio_settings}}');
    }
}