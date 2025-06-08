<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_group}}`.
 */
class m250607_170329_create_user_group_table extends Migration
{
    public function safeUp()
    {
        // создаём связь user–group
        $this->createTable('{{%user_group}}', [
            'user_id'  => $this->integer()->notNull(),
            'group_id' => $this->integer()->notNull(),
        ]);

        // составной первичный ключ
        $this->addPrimaryKey('pk-user_group', '{{%user_group}}', ['user_id', 'group_id']);

        // внешние ключи
        $this->addForeignKey('fk-user_group-user', '{{%user_group}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-user_group-group', '{{%user_group}}', 'group_id', '{{%group}}', 'id', 'CASCADE');

        // сразу связываем admin (id=1) с группой admin (id=1)
        $this->insert('{{%user_group}}', [
            'user_id'  => 1,
            'group_id' => 1,
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-user_group-user', '{{%user_group}}');
        $this->dropForeignKey('fk-user_group-group', '{{%user_group}}');
        $this->dropPrimaryKey('pk-user_group', '{{%user_group}}');
        $this->dropTable('{{%user_group}}');
    }
}