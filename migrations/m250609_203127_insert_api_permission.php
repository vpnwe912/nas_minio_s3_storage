<?php

use yii\db\Migration;

class m250609_203127_insert_api_permission extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%permission}}', [
            'name' => 'access_api',
            'comment' => 'Доступ к API получения конфигов MinIO'
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%permission}}', ['name' => 'access_api']);
    }
}
