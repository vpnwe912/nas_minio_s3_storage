<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_token}}`.
 */
class m250612_165700_create_user_token_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_token}}', [
            'id'         => $this->primaryKey(),
            'user_id'    => $this->integer()->notNull(),
            'token'      => $this->string(255)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
        ]);
        // Внешний ключ на user (при удалении пользователя токены удаляются)
        $this->addForeignKey(
            'fk_user_token_user',
            '{{%user_token}}', 'user_id',
            '{{%user}}', 'id',
            'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_user_token_user', '{{%user_token}}');
        $this->dropTable('{{%user_token}}');
    }
}