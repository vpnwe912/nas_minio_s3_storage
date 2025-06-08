<?php
namespace app\components;

use Yii;

/**
 * Служба для управления MinIO через mc (MinIO Client).
 * Убедитесь, что на сервере установлен mc и доступен из PHP.
 */
class MinioAdminService
{
    // public string $alias = 'local';
    public string $alias;

    public function __construct()
    {
        // Настраиваем alias mc при каждом вызове
        $host   = $_ENV['MINIO_HOST'];
        $key    = $_ENV['MINIO_KEY'];
        $secret = $_ENV['MINIO_SECRET'];
        $this->alias = $_ENV['MINIO_ALIAS'] ?? 'local';

        exec("mc alias set {$this->alias} {$host} {$key} {$secret}", $out, $code);
        if ($code !== 0) {
            Yii::error("mc alias set failed:\n" . implode("\n", $out));
        }
    }

    // --- Пользователи ---

/**
 * Список пользователей: возвращает [['user'=>'alice','policies'=>'readwrite'], …]
 */
public function listUsers(): array
{
    exec("mc admin user list {$this->alias}", $out, $code);
    if ($code !== 0) {
        Yii::error("mc admin user list failed:\n" . implode("\n", $out));
        return [];
    }

    $res = [];
    foreach ($out as $line) {
        $line = trim($line);
        // обрабатываем только строки, начинающиеся на enabled или disabled
        if (!preg_match('/^(enabled|disabled)\s+/i', $line)) {
            continue;
        }
        // разбиваем на статус, имя и (опционально) политики
        // пример строки: "enabled alice readwrite,readonly"
        if (preg_match('/^(?:enabled|disabled)\s+(\S+)(?:\s+(.*))?$/i', $line, $m)) {
            $user     = $m[1];
            $policies = $m[2] ?? '';
            $res[]    = ['user' => $user, 'policies' => $policies];
        }
    }
    return $res;
}



    /** Добавить пользователя */
    public function createUser(string $user, string $pass): bool
    {
        exec("mc admin user add {$this->alias} {$user} {$pass}", $out, $code);
        return $code === 0;
    }

    /** Удалить пользователя */
    public function deleteUser(string $user): bool
    {
        exec("mc admin user remove {$this->alias} {$user}", $out, $code);
        return $code === 0;
    }

    /** Назначить политику пользователю */
    public function setUserPolicy(string $user, string $policy): bool
    {
        exec("mc admin policy set {$this->alias} {$policy} user={$user}", $out, $code);
        return $code === 0;
    }


    // --- Группы ---

    /**
     * Список групп с их статусом, участниками и политиками
     *
     * @return array [
     *   ['group'=>'dev','status'=>'enabled','users'=>'alice,bob','policies'=>'readonly'], …
     * ]
     */
    public function listGroups(): array
    {
        // 1) получаем имена групп
        exec("mc admin group list {$this->alias} 2>&1", $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin group list failed:\n" . implode("\n", $out));
            return [];
        }

        $groupNames = [];
        foreach ($out as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'GROUP') === 0) {
                continue;
            }
            $groupNames[] = preg_split('/\s+/', $line)[0];
        }

        // 2) для каждой группы собираем инфо
        $res = [];
        foreach ($groupNames as $name) {
            $info = $this->getGroupInfo($name);
            $res[] = [
                'group'    => $name,
                'status'   => $info['status'],
                'users'    => implode(', ', $info['members']),
                'policies' => implode(', ', $info['policies']),
            ];
        }

        return $res;
    }


    /**
     * Создать группу с одним участником (root-пользователь из .env)
     *
     * @param string $group  — имя группы
     * @return bool
     */
    public function createGroup(string $group, array $members): bool
    {
        // если не передали ни одного – выходим с ошибкой
        if (empty($members)) {
            Yii::error("MinioAdminService::createGroup: no members provided for group {$group}");
            return false;
        }

        // формируем команду: mc admin group add alias group member1 member2 …
        $parts = array_merge(
            ['mc','admin','group','add',$this->alias,$group],
            $members
        );
        // экранируем каждый
        $esc   = array_map('escapeshellarg', $parts);
        $cmd   = implode(' ', $esc) . ' 2>&1';

        exec($cmd, $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin group add failed (cmd: {$cmd}):\n" . implode("\n", $out));
        }
        return $code === 0;
    }

    /** Удалить группу */
    public function deleteGroup(string $group): bool
    {
        exec("mc admin group remove {$this->alias} {$group}", $out, $code);
        return $code === 0;
    }

    /** Добавить пользователя в группу */
    public function addUserToGroup(string $group, string $user): bool
    {
        // mc admin group add <alias> <group> <member>
        exec("mc admin group add {$this->alias} {$group} {$user}", $out, $code);
        return $code === 0;
    }

    /** Удалить пользователя из группы */
    public function removeUserFromGroup(string $group, string $user): bool
    {
        // mc admin group remove <alias> <group> <member>
        exec("mc admin group remove {$this->alias} {$group} {$user}", $out, $code);
        return $code === 0;
    }

    /**
     * Назначить политику группе
     */
    public function setGroupPolicy(string $group, string $policy): bool
    {
        // mc admin policy attach <alias> <policyName> --group <groupName>
        $cmd = sprintf(
            'mc admin policy attach %s %s --group %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($policy),
            escapeshellarg($group)
        );
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin policy attach failed (cmd: {$cmd}):\n" . implode("\n", $out));
        }
        return $code === 0;
    }

    /**
     * Снять политику с группы
     */
    public function removeGroupPolicy(string $group, string $policy): bool
    {
        // mc admin policy detach <alias> <policyName> --group <groupName>
        $cmd = sprintf(
            'mc admin policy detach %s %s --group %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($policy),
            escapeshellarg($group)
        );
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin policy detach failed (cmd: {$cmd}):\n" . implode("\n", $out));
        }
        return $code === 0;
    }

    /**
     * Возвращает информацию по группе: статус, участников и назначенные политики
     *
     * @param string $group
     * @return array ['status'=>'enabled','members'=>string[],'policies'=>string[]]
     */
    public function getGroupInfo(string $group): array
    {
        exec("mc admin group info {$this->alias} {$group} 2>&1", $out, $code);

        $status   = 'unknown';
        $members  = [];
        $policies = [];

        if ($code === 0) {
            foreach ($out as $line) {
                $line = trim($line);
                if (preg_match('/^Status:\s*(.+)$/i', $line, $m)) {
                    $status = strtolower($m[1]);
                }
                if (preg_match('/^Members:\s*(.+)$/i', $line, $m)) {
                    $members = array_map('trim', explode(',', $m[1]));
                }
                if (preg_match('/^Policy:\s*(.*)$/i', $line, $p)) {
                    $policies = $p[1] !== '' 
                        ? array_map('trim', explode(',', $p[1])) 
                        : [];
                }
            }
        } else {
            Yii::error("mc admin group info failed for {$group}:\n" . implode("\n", $out));
        }

        return [
            'status'   => $status,
            'members'  => $members,
            'policies' => $policies,
        ];
    }

    /**
     * Отключить группу
     */
    public function disableGroup(string $group): bool
    {
        exec("mc admin group disable {$this->alias} {$group} 2>&1", $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin group disable failed:\n" . implode("\n", $out));
        }
        return $code === 0;
    }

    /**
     * Включить группу
     */
    public function enableGroup(string $group): bool
    {
        exec("mc admin group enable {$this->alias} {$group} 2>&1", $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin group enable failed:\n" . implode("\n", $out));
        }
        return $code === 0;
    }


    // --- Политики ---

    /** Список политик: [['policy'=>'readonly'], …] */
    public function listPolicies(): array
    {
        exec("mc admin policy list {$this->alias}", $out, $code);
        if ($code !== 0) { Yii::error("mc admin policy list failed"); return []; }
        $res = [];
        foreach ($out as $line) {
            if (trim($line) && $line !== 'NAME') {
                $res[] = ['policy'=>trim($line)];
            }
        }
        return $res;
    }

    /**
     * Создать или обновить политику (новая команда вместо add)
     */
    public function createPolicy(string $name, string $file): bool
    {
        // mc admin policy create <alias> <policyName> <file.json>
        $cmd = sprintf(
            'mc admin policy create %s %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($name),
            escapeshellarg($file)
        );
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Yii::error("mc admin policy create failed (cmd: {$cmd}):\n" . implode("\n", $out));
        }
        return $code === 0;
    }

    /** Удалить политику */
    public function deletePolicy(string $name): bool
    {
        exec("mc admin policy remove {$this->alias} {$name}", $out, $code);
        return $code === 0;
    }

    /**
     * Экспортирует JSON политики в файл (для редактирования)
     *
     * @param string $name      — имя политики
     * @param string $destFile  — полный путь, куда сохранить JSON
     * @return bool
     */
    public function exportPolicy(string $name, string $destFile): bool
    {
        // 1) Получаем JSON политики на STDOUT
        $cmd = sprintf(
            'mc admin policy info %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($name)
        );
        Yii::error(">> exportPolicy cmd: {$cmd}");
        exec($cmd, $out, $code);

        if ($code !== 0) {
            Yii::error("<< exportPolicy FAILED code={$code}\n" . implode("\n", $out));
            return false;
        }

        // 2) Собираем строки в один JSON и сохраняем
        $json = implode("\n", $out);
        if (file_put_contents($destFile, $json) === false) {
            Yii::error("<< exportPolicy FAILED to write file {$destFile}");
            return false;
        }

        Yii::error("<< exportPolicy OK, saved to {$destFile}");
        return true;
    }


    /**
     * 
     * 
     */
    public function importPolicy(string $name, string $jsonFile): bool
    {
        $cmd = sprintf(
            'mc admin policy import %s %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($name),
            escapeshellarg($jsonFile)
        );
        Yii::error(">> importPolicy cmd: {$cmd}");
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Yii::error("<< importPolicy FAILED code={$code}\n" . implode("\n", $out));
        } else {
            Yii::error("<< importPolicy OK");
        }
        return $code === 0;
    }
}
