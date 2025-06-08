<?php

use yii\db\Migration;

class m250607_174647_insert_default_permissions extends Migration
{
    public function safeUp()
    {
        // 1) список прав
        $perms = [
            'viewUsers', 'createUsers', 'updateUsers', 'deleteUsers',
            'viewGroups','createGroups','updateGroups','deleteGroups',
            // при желании добавьте другие:
            'viewBuckets','createBuckets','deleteBuckets',
            'uploadFiles','deleteFiles',
            'manageServers','createServices','deleteServices',
            'importConfig','exportConfig',
        ];
        // вставляем в таблицу permission
        foreach ($perms as $name) {
            $this->insert('{{%permission}}',['name'=>$name]);
        }
        // 2) все права даём группе admin (id=1)
        $rows = $this->db
            ->createCommand('SELECT id FROM {{%permission}}')
            ->queryColumn();
        foreach ($rows as $pid) {
            $this->insert('{{%group_permission}}',[
                'group_id'      => 1,
                'permission_id' => $pid,
            ]);
        }
    }

    public function safeDown()
    {
        // удаляем все записи
        $this->delete('{{%group_permission}}');
        $this->delete('{{%permission}}');
    }
}
