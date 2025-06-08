<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user}}`.
 */
class m250607_170224_create_user_table extends Migration
{
    public function safeUp()
    {
        // 1) создаём таблицу user
        $this->createTable('{{%user}}', [
            'id'             => $this->primaryKey(),
            'username'       => $this->string()->notNull()->unique(),
            'email'          => $this->string()->notNull()->unique(),
            'salt'           => $this->string(32)->notNull(),
            'password_hash'  => $this->string(255)->notNull(),
            'auth_key'       => $this->string(32)->notNull(),
            'created_at'     => $this->integer()->notNull(),
        ]);

        // 2) создаём первую соль и хэш пароля для admin/admin
        $security = Yii::$app->security;
        $salt     = base64_encode(random_bytes(16));
        $hash     = $security->generatePasswordHash('admin' . $salt);
        $authKey  = $security->generateRandomString();

        // 3) вставляем пользователя admin
        $this->insert('{{%user}}', [
            'username'      => 'admin',
            'email'         => 'admin@example.com',
            'salt'          => $salt,
            'password_hash' => $hash,
            'auth_key'      => $authKey,
            'created_at'    => time(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}