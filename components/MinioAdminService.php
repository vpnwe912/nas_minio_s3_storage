<?php
namespace app\components;

use Yii;

/**
 * Служба для управления MinIO через mc (MinIO Client).
 * Убедитесь, что на сервере установлен mc и доступен из PHP.
 */
class MinioAdminService
{
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

        return $this->exec($cmd, 'Не удалось создать группу') !== false;
    }

    /** Удалить группу */
    public function deleteGroup(string $group): bool
    {
        $cmd = sprintf('mc admin group remove %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($group)
        );
        return $this->exec($cmd, 'Не удалось удалить группу') !== false;
    }

    /** Добавить пользователя в группу */
    public function addUserToGroup(string $group, string $user): bool
    {
        // mc admin group add <alias> <group> <member>
        $cmd = sprintf('mc admin group add %s %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($group),
            escapeshellarg($user)
        );
        return $this->exec($cmd, 'Не удалось добавить пользователя в группу') !== false;
    }

    /** Удалить пользователя из группы */
    public function removeUserFromGroup(string $group, string $user): bool
    {
        // mc admin group remove <alias> <group> <member>
        $cmd = sprintf('mc admin group remove %s %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($group),
            escapeshellarg($user)
        );
        return $this->exec($cmd, 'Не удалось удалить пользователя из группы') !== false;
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
        return $this->exec($cmd, 'Не удалось назначить политику группе') !== false;
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
        return $this->exec($cmd, 'Не удалось снять политику с группы') !== false;
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

    public function listPolicies()
    {
        $cmd = sprintf('mc admin policy list %s 2>&1', 
            escapeshellarg($this->alias)
        );
        
        $output = $this->exec($cmd, 'Ошибка получения списка политик');
        if ($output === false) return [];

        // var_dump($output); die();
        
        return array_filter(array_map('trim', $output), function($line) {
            return $line !== '' && strtolower($line) !== 'name';
        });
    }
    
    public function getPolicy($name)
    {
        $cmd = sprintf('mc admin policy info %s %s --json 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($name)
        );
        
        $result = $this->exec($cmd, 'Ошибка получения политики');
        if ($result === false) return null;
        
        $data = json_decode(implode("\n", $result), true);
        return $data['policyInfo']['Policy'] ?? null;
    }
    


    // -------------------- start savePolicy --------------------








    public function savePolicy($name, $model)
    {
            $policy = [
            'Version' => '2012-10-17',
            'Statement' => []
        ];
        $debugDir = __DIR__ . '/../runtime/minio-police';
        if (!is_dir($debugDir)) mkdir($debugDir, 0777, true);
    
        $bucket = trim($model->bucket);
        $folders = array_values($model->folders);
        $actionsList = array_values($model->actions);
    
        // ListBucket
        $allPrefixes = [];
        foreach ($folders as $folder) {
            $allPrefixes[] = trim($folder, '/');
        }
        $sidList = "List" . ucfirst($bucket) . (count($allPrefixes) > 1 ? "Folders" : "Folder")
            . "_" . substr(md5(implode(',', $allPrefixes)), 0, 8);
    
        $policy['Statement'][] = [
            'Sid' => $sidList,
            'Effect' => "Allow",
            'Action' => ["s3:ListBucket"],
            'Resource' => ["arn:aws:s3:::$bucket"],
            'Condition' => [
                'StringLike' => [
                    's3:prefix' => array_map(fn($p) => "$p/*", $allPrefixes)
                ]
            ]
        ];
    
        // Group object-actions per unique set
        $objectActionGroups = [];
        foreach ($folders as $idx => $folder) {
            $folder = trim($folder, '/');
            $actions = $actionsList[$idx] ?? [];
            $objActions = array_values(array_diff($actions, ['s3:ListBucket']));
            if (!$objActions) continue;
            $key = implode(',', $objActions);
            if (!isset($objectActionGroups[$key])) $objectActionGroups[$key] = [];
            $objectActionGroups[$key][] = $folder;
        }
    
        foreach ($objectActionGroups as $actionsKey => $prefixes) {
            $actionsArr = explode(',', $actionsKey);
            $resources = [];
            foreach ($prefixes as $prefix) {
                $resources[] = "arn:aws:s3:::$bucket/$prefix/*";
            }
            $policy['Statement'][] = [
                'Sid' => 'Access' . ucfirst($bucket) . (count($prefixes) > 1 ? "Folders" : "Folder")
                    . "_" . substr(md5($actionsKey . implode(',', $prefixes)), 0, 8),
                'Effect' => 'Allow',
                'Action' => $actionsArr,
                'Resource' => $resources,
            ];
        }
    
        // DEBUG
        file_put_contents($debugDir . '/debug_policy.json', json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $tmpFile = tempnam(sys_get_temp_dir(), 'minio_policy_');
        file_put_contents($tmpFile, json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
        // Сохраняем комментарий (один на всю политику)
        \app\models\PolicyMeta::savePolicyComment($name, $model->comment);
    
        try {
            $cmd = sprintf('mc admin policy create %s %s %s 2>&1',
                escapeshellarg($this->alias),
                escapeshellarg($name),
                escapeshellarg($tmpFile)
            );
            $res = $this->exec($cmd, 'Ошибка сохранения политики') !== false;
            return $res;
        } finally {
            @unlink($tmpFile);
        }
    }
    
    
    


    
    
    

    // -------------------- end savePolicy --------------------
    

    public function saveMetaComments($policyName, $metaToSave)
    {
        foreach ($metaToSave as $meta) {
            if (!empty($meta['sid'])) {
                \app\models\PolicyMeta::savePolicyComment($policyName, $meta['sid'], $meta['comment']);
            }
        }
    }

    public function deletePolicy($name)
    {
        $cmd = sprintf('mc admin policy remove %s %s 2>&1',
            escapeshellarg($this->alias),
            escapeshellarg($name)
        );
        
        return $this->exec($cmd, 'Ошибка удаления политики') !== false;
    }
    
    private function exec($cmd, $errorMessage)
    {
        $output = [];
        $code = 0;
        
        exec($cmd, $output, $code);
        
        if ($code !== 0) {
            Yii::error("{$errorMessage} (код {$code}): " . implode("\n", $output));
            return false;
        }
        
        return $output;
    }

}
