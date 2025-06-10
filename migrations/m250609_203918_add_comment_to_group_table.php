<?php

use yii\db\Migration;

class m250609_203918_add_comment_to_group_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%group}}', 'comment', $this->string()->null()->after('name'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%group}}', 'comment');
    }
}

