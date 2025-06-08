<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%group}}`.
 */
class m250607_170259_create_group_table extends Migration
{
    public function safeUp()
    {
        // создаём таблицу group
        $this->createTable('{{%group}}', [
            'id'   => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
        ]);

        // создаём группу admin
        $this->insert('{{%group}}', [
            'name' => 'admin',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%group}}');
    }
}
