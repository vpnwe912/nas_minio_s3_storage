<?php

use yii\db\Migration;

class m250609_202713_crete_minio_configs_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%minio_configs}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'provider' => $this->string()->notNull()->defaultValue('Minio'),
            'endpoint' => $this->string()->notNull(),
            'region' => $this->string()->notNull()->defaultValue('us-east-1'),
            'location_constraint' => $this->string()->null(),
            'comment' => $this->text()->null(),
            'is_default' => $this->boolean()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')
        ]);

        // Вставим дефолтный шаблон (можешь менять значения)
        $this->insert('{{%minio_configs}}', [
            'name' => 'default',
            'provider' => 'Minio',
            'endpoint' => 'http://localhost:9000',
            'region' => 'us-east-1',
            'location_constraint' => '',
            'comment' => 'Default MinIO config',
            'is_default' => 1,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%minio_configs}}');
    }
}