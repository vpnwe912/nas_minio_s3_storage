<?php

use yii\db\Migration;

class m250609_204005_create_api_group_and_assign_permission extends Migration
{
    public function safeUp()
    {
        // 1. Добавляем новую группу "api"
        $this->insert('{{%group}}', [
            'name' => 'api',
            'comment' => 'Группа для пользователей с доступом к API',
        ]);
        // Получаем id созданной группы (можно получить через lastInsertID, но чтобы гарантировать — найдём по имени)
        $groupId = $this->db->createCommand('SELECT id FROM {{%group}} WHERE name=:name')
            ->bindValue(':name', 'api')
            ->queryScalar();

        // 2. Добавляем permission "access_api", если вдруг его нет (на всякий случай)
        $permId = $this->db->createCommand('SELECT id FROM {{%permission}} WHERE name=:name')
            ->bindValue(':name', 'access_api')
            ->queryScalar();
        if (!$permId) {
            $this->insert('{{%permission}}', [
                'name' => 'access_api',
                'comment' => 'Доступ к API получения конфигов MinIO'
            ]);
            $permId = $this->db->createCommand('SELECT id FROM {{%permission}} WHERE name=:name')
                ->bindValue(':name', 'access_api')
                ->queryScalar();
        }

        // 3. Связываем permission с группой
        $this->insert('{{%group_permission}}', [
            'group_id'      => $groupId,
            'permission_id' => $permId,
        ]);
    }

    public function safeDown()
    {
        // Получаем id permission
        $permId = $this->db->createCommand('SELECT id FROM {{%permission}} WHERE name=:name')
            ->bindValue(':name', 'access_api')
            ->queryScalar();
        // Получаем id группы
        $groupId = $this->db->createCommand('SELECT id FROM {{%group}} WHERE name=:name')
            ->bindValue(':name', 'api')
            ->queryScalar();

        if ($groupId && $permId) {
            $this->delete('{{%group_permission}}', [
                'group_id' => $groupId,
                'permission_id' => $permId,
            ]);
        }

        if ($permId) {
            $this->delete('{{%permission}}', ['id' => $permId]);
        }
        if ($groupId) {
            $this->delete('{{%group}}', ['id' => $groupId]);
        }
    }
}
