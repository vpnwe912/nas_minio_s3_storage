<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class BinariesController extends Controller
{
    private $files = [
        ['folder' => 'downloads/minio-client-debian',   'filename' => 'minio'],
        ['folder' => 'downloads/minio-client-windows',  'filename' => 'mc.exe'],
        ['folder' => 'downloads/minio-server-debian',   'filename' => 'minio'],
        ['folder' => 'downloads/minio-server-windows',  'filename' => 'minio.exe'],
        ['folder' => 'downloads/rclone',                'filename' => 'rclone.exe'],
        ['folder' => 'downloads/winfsp',                'filename' => 'winfsp.msi'],
    ];

    public function actionDownloadAll()
    {
        $user   = $_ENV['GITHUB_USER']   ?? null;
        $repo   = $_ENV['GITHUB_REPO']   ?? null;
        $tag    = $_ENV['GITHUB_TAG']    ?? null;
        $token  = $_ENV['GITHUB_TOKEN']  ?? null;

        if (!$user || !$repo || !$tag) {
            $this->stderr("ERROR: Не заданы переменные окружения GITHUB_USER/GITHUB_REPO/GITHUB_TAG\n");
            return;
        }

        foreach ($this->files as $file) {
            $this->stdout("=== Скачиваем {$file['filename']}...\n");
            $result = $this->downloadFromGithubRelease([
                'user'     => $user,
                'repo'     => $repo,
                'tag'      => $tag,
                'token'    => $token,
                'folder'   => $file['folder'],
                'filename' => $file['filename'],
            ]);
            if ($result) {
                $this->stdout("✔ {$file['filename']} скачан успешно.\n");
            } else {
                $this->stderr("✖ {$file['filename']} не удалось скачать!\n");
            }
        }
        $this->stdout("=== Все файлы обработаны ===\n");
    }

    private function downloadFromGithubRelease($params)
    {
        $fullFolder = Yii::getAlias('@app/' . $params['folder']);
        if (!is_dir($fullFolder)) mkdir($fullFolder, 0775, true);
        $savePath = $fullFolder . DIRECTORY_SEPARATOR . $params['filename'];

        // 1. Публичный способ (если нет токена)
        if (empty($params['token'])) {
            $directUrl = "https://github.com/{$params['user']}/{$params['repo']}/releases/download/{$params['tag']}/{$params['filename']}";
            $this->stdout("Публичный способ: $directUrl\n");

            $fp = fopen($savePath, 'w');
            $ch = curl_init($directUrl);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH'); // Для Win-php
            curl_setopt($ch, CURLOPT_VERBOSE, true); // debug

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $this->stderr("CURL ERROR: " . curl_error($ch) . "\n");
            }
            fclose($fp);
            curl_close($ch);

            if ($httpCode === 200) {
                return $this->checkBinaryFile($savePath, $params['filename']);
            } else {
                $this->stderr("HTTP ошибка: $httpCode при скачивании $directUrl\n");
                @unlink($savePath);
                return false;
            }
        }

        // 2. Приватный способ через GitHub API
        $apiUrl = "https://api.github.com/repos/{$params['user']}/{$params['repo']}/releases/tags/{$params['tag']}";
        $headers = [
            "Authorization: token {$params['token']}",
            "Accept: application/vnd.github.v3+json",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        ];

        $this->stdout("Запрос к API релиза: $apiUrl\n");
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_VERBOSE, true); // debug

        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->stderr("CURL ERROR: " . curl_error($ch) . "\n");
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->stderr("Ошибка API релиза: HTTP $httpCode\n");
            return false;
        }
        $release = json_decode($json, true);
        if (empty($release['assets'])) {
            $this->stderr("В релизе нет assets для тега {$params['tag']}.\n");
            return false;
        }

        $asset = null;
        foreach ($release['assets'] as $a) {
            if ($a['name'] === $params['filename']) {
                $asset = $a;
                break;
            }
        }
        if (!$asset) {
            $this->stderr("Файл {$params['filename']} не найден в релизе {$params['tag']}.\n");
            return false;
        }

        // Качаем бинарь по asset['url'] через API (обязательно Accept: application/octet-stream)
        $downloadUrl = $asset['url'];
        $this->stdout("API asset download url: $downloadUrl\n");

        $fp = fopen($savePath, 'w');
        $downloadHeaders = [
            "Authorization: token {$params['token']}",
            "Accept: application/octet-stream",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        ];
        $ch = curl_init($downloadUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $downloadHeaders);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_VERBOSE, true); // debug

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->stderr("CURL ERROR: " . curl_error($ch) . "\n");
        }
        fclose($fp);
        curl_close($ch);

        if ($httpCode === 200) {
            return $this->checkBinaryFile($savePath, $params['filename']);
        } else {
            $this->stderr("HTTP ошибка: $httpCode при скачивании asset $downloadUrl\n");
            @unlink($savePath);
            return false;
        }
    }

    private function checkBinaryFile($savePath, $filename)
    {
        if (filesize($savePath) < 100 * 1024) {
            $content = file_get_contents($savePath);
            if (strpos($content, '<html') !== false) {
                $this->stderr("Файл {$filename} не скачан! Получен HTML вместо бинарника!\n");
                $this->stderr("Фрагмент ответа:\n" . mb_substr($content, 0, 200) . "\n---\n");
                @unlink($savePath);
                return false;
            }
        }
        return true;
    }
}
