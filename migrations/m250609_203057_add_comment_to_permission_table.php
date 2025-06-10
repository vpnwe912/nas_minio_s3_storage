<?php

use yii\db\Migration;

class m250609_203057_add_comment_to_permission_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%permission}}', 'comment', $this->string()->null()->after('name'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%permission}}', 'comment');
    }
}
