<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%binaries}}`.
 */
class m250618_110918_create_binaries_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%binaries}}', [
            'id'         => $this->primaryKey(),
            'name'       => $this->string(64)->notNull(),      // rclone, winfsp
            'filename'   => $this->string(128)->notNull(),     // rclone.exe, winfsp.msi и т.д.
            'version'    => $this->string(32)->notNull(),      // 1.66.0, 2.1.25156
            'type'       => $this->string(16)->notNull(),      // exe, msi, zip
            'path'       => $this->string(255)->notNull(),     // downloads/rclone/rclone.exe
            'size'       => $this->integer()->notNull(),
            'hash'       => $this->string(128),                // SHA256
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'description'=> $this->text()->null(),
        ]);

        $this->createIndex('idx-binaries-name-filename', '{{%binaries}}', ['name', 'filename'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%binaries}}');
    }
}
