<?php

use yii\db\Migration;

class m250609_194312_add_can_login_to_user_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'can_login', $this->boolean()->notNull()->defaultValue(1));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'can_login');
    }
}
