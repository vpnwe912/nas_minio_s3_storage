<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%permission}}`.
 */
class m250607_170350_create_permission_table extends Migration
{
    public function safeUp()
    {
        // таблица прав
        $this->createTable('{{%permission}}', [
            'id'   => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%permission}}');
    }
}
