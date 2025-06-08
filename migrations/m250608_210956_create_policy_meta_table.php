<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%policy_meta}}`.
 */
class m250608_210956_create_policy_meta_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%policy_meta}}', [
            'policy_name' => $this->string()->notNull(),
            'sid'         => $this->string()->notNull(),
            'comment'     => $this->text()->null(),
            'PRIMARY KEY(policy_name, sid)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%policy_meta}}');
    }
}
