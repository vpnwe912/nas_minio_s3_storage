<?php

use yii\db\Migration;

class m250609_193825_crete_minio_accounts_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%minio_accounts}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),         // FK на таблицу пользователей сайта
            'minio_access_key' => $this->string()->notNull(), // логин MinIO
            'minio_secret_key' => $this->string()->notNull(), // пароль MinIO
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex(
            'idx-minio_accounts-user_id',
            '{{%minio_accounts}}',
            'user_id'
        );
        $this->addForeignKey(
            'fk-minio_accounts-user_id',
            '{{%minio_accounts}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
        $this->createIndex(
            'idx-minio_accounts-access_key',
            '{{%minio_accounts}}',
            'minio_access_key',
            true
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-minio_accounts-user_id', '{{%minio_accounts}}');
        $this->dropTable('{{%minio_accounts}}');
    }
}