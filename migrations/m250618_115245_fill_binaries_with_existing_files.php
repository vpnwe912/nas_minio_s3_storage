<?php

use yii\db\Migration;

class m250618_115245_fill_binaries_with_existing_files extends Migration
{
    public function safeUp()
    {
        // Корневая папка для бинарников
        $baseDir = Yii::getAlias('@app/downloads');
        $rows = [];

        // Получаем список всех подпапок (имя подпапки = name)
        foreach (glob($baseDir . '/*', GLOB_ONLYDIR) as $folder) {
            $name = basename($folder);
            foreach (glob($folder . '/*') as $filePath) {
                if (is_file($filePath)) {
                    $filename = basename($filePath);
                    $version = '1.0'; // Если надо, измени на нужную логику!
                    $type = pathinfo($filename, PATHINFO_EXTENSION);
                    $size = filesize($filePath);
                    $hash = hash_file('sha256', $filePath);
                    $path = "downloads/$name/$filename";
                    $created_at = date('Y-m-d H:i:s', filectime($filePath));
                    $updated_at = date('Y-m-d H:i:s', filemtime($filePath));
                    $description = null;

                    // Попробуй вытащить версию из имени (например rclone-1.66.0.exe)
                    if (preg_match('/([0-9]+\.[0-9]+(\.[0-9]+)?)/', $filename, $m)) {
                        $version = $m[1];
                    }

                    $rows[] = [
                        'name' => $name,
                        'filename' => $filename,
                        'version' => $version,
                        'type' => $type,
                        'path' => $path,
                        'size' => $size,
                        'hash' => $hash,
                        'created_at' => $created_at,
                        'updated_at' => $updated_at,
                        'description' => $description,
                    ];
                }
            }
        }

        // Добавляем в базу
        foreach ($rows as $row) {
            $this->insert('{{%binaries}}', $row);
        }
    }

    public function safeDown()
    {
        // Можешь удалить только то, что было добавлено (например, по папкам)
        $baseDir = Yii::getAlias('@app/downloads');
        foreach (glob($baseDir . '/*', GLOB_ONLYDIR) as $folder) {
            $name = basename($folder);
            $this->delete('{{%binaries}}', ['name' => $name]);
        }
    }
}
