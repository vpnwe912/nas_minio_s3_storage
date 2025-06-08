<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%group_permission}}`.
 */
class m250607_170412_create_group_permission_table extends Migration
{
    public function safeUp()
    {
        // связь группа–право
        $this->createTable('{{%group_permission}}', [
            'group_id'      => $this->integer()->notNull(),
            'permission_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk-group_permission', '{{%group_permission}}', ['group_id', 'permission_id']);

        $this->addForeignKey('fk-group_permission-group', '{{%group_permission}}', 'group_id', '{{%group}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-group_permission-permission', '{{%group_permission}}', 'permission_id', '{{%permission}}', 'id', 'CASCADE');

        // (пока без начальных записей; позже права можно добавлять в админке)
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-group_permission-group', '{{%group_permission}}');
        $this->dropForeignKey('fk-group_permission-permission', '{{%group_permission}}');
        $this->dropPrimaryKey('pk-group_permission', '{{%group_permission}}');
        $this->dropTable('{{%group_permission}}');
    }
}
