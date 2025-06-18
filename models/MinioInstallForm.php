<?php

namespace app\models;

use Yii;
use yii\base\Model;

class MinioInstallForm extends Model
{
    public $installType = 'download'; // download или copy
    public $minioLocalFile = '@app/downloads/minio-server-debian/minio';
    public $mcLocalFile = '@app/downloads/minio-client-debian/mc';
    public $minioUser = 'minio-user';
    public $minioDir = '/home/minio-user';
    public $dataDir = '/data';
    public $minioPath = '/usr/local/bin/minio';
    public $serviceName = 'minio';
    public $rootUser = 'minioadmin';
    public $rootPassword = 'minioadmin';
    // Параметры alias для mc
    public $mcAlias = 'local';
    public $mcAliasUrl = 'http://127.0.0.1:9000';

    public function rules()
    {
        return [
            [['installType', 'minioUser', 'minioDir', 'dataDir', 'serviceName', 'minioPath', 'rootUser', 'rootPassword', 'mcAlias', 'mcAliasUrl'], 'required'],
            [['minioLocalFile', 'mcLocalFile'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'installType'      => 'Тип установки',
            'minioLocalFile'   => 'Локальный путь к minio (сервер)',
            'mcLocalFile'      => 'Локальный путь к mc (клиент)',
            'minioUser'        => 'Имя пользователя MinIO',
            'minioDir'         => 'Домашняя директория пользователя',
            'dataDir'          => 'Директория данных MinIO',
            'serviceName'      => 'Название сервиса',
            'minioPath'        => 'Путь к бинарнику minio',
            'rootUser'         => 'MinIO Root User (логин)',
            'rootPassword'     => 'MinIO Root Password',
            'mcAlias'          => 'Имя alias для mc',
            'mcAliasUrl'       => 'URL MinIO для mc',
        ];
    }

    public function processInstall()
    {
        // 1. Установка бинарников
        if ($this->installType == 'download') {
            // MinIO server
            exec("wget https://dl.min.io/server/minio/release/linux-amd64/minio -O /tmp/minio", $out1, $c1);
            exec("sudo mv /tmp/minio {$this->minioPath}");
            // MinIO client
            exec("wget https://dl.min.io/client/mc/release/linux-amd64/mc -O /tmp/mc", $out1b, $c1b);
            exec("sudo mv /tmp/mc /usr/local/bin/mc");
        } else {
            $minioLocal = Yii::getAlias($this->minioLocalFile);
            $mcLocal    = Yii::getAlias($this->mcLocalFile);
            exec("sudo cp $minioLocal {$this->minioPath}");
            exec("sudo cp $mcLocal /usr/local/bin/mc");
        }
        exec("sudo chmod +x {$this->minioPath} /usr/local/bin/mc");

        // 2. Создание пользователя и директорий
        exec("sudo useradd -r {$this->minioUser} -s /sbin/nologin");
        exec("sudo mkdir -p {$this->minioDir}");
        exec("sudo chown {$this->minioUser}:{$this->minioUser} {$this->minioDir}");
        exec("sudo mkdir -p {$this->dataDir}");
        exec("sudo chown {$this->minioUser}:{$this->minioUser} {$this->dataDir}");

        // 3. Создание /etc/default/minio с логином/паролем
        $env = "MINIO_ROOT_USER={$this->rootUser}\nMINIO_ROOT_PASSWORD={$this->rootPassword}\n";
        file_put_contents('/tmp/minio_env', $env);
        exec("sudo mv /tmp/minio_env /etc/default/minio");
        exec("sudo chown root:root /etc/default/minio");
        exec("sudo chmod 600 /etc/default/minio");

        // 4. Создание systemd unit
        $unit = "[Unit]
Description=MinIO
Documentation=https://docs.min.io
Wants=network-online.target
After=network-online.target

[Service]
User={$this->minioUser}
Group={$this->minioUser}
EnvironmentFile=-/etc/default/minio
ExecStart={$this->minioPath} server {$this->dataDir}
Restart=always
RestartSec=5
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
";
        file_put_contents('/tmp/minio.service', $unit);
        exec("sudo mv /tmp/minio.service /etc/systemd/system/{$this->serviceName}.service");
        exec("sudo chmod 644 /etc/systemd/system/{$this->serviceName}.service");

        // 5. systemctl enable и запуск
        exec("sudo systemctl daemon-reload");
        exec("sudo systemctl enable {$this->serviceName}");
        exec("sudo systemctl start {$this->serviceName}");

        // 6. Конфигурирование mc alias (после установки mc и запуска minio)
        // Чтобы работало - mc должен быть доступен в $PATH!
        $cmdAlias = "mc alias set {$this->mcAlias} {$this->mcAliasUrl} {$this->rootUser} {$this->rootPassword}";
        exec("sudo -u {$this->minioUser} /usr/local/bin/mc alias rm {$this->mcAlias}"); // На всякий случай удаляем старый alias
        exec("sudo -u {$this->minioUser} $cmdAlias");

        // 7. Сохраняем параметры
        \app\models\MinioSettings::setValue('minio_user', $this->minioUser);
        \app\models\MinioSettings::setValue('minio_dir', $this->minioDir);
        \app\models\MinioSettings::setValue('data_dir', $this->dataDir);
        \app\models\MinioSettings::setValue('service_name', $this->serviceName);
        \app\models\MinioSettings::setValue('minio_path', $this->minioPath);
        \app\models\MinioSettings::setValue('root_user', $this->rootUser);
        \app\models\MinioSettings::setValue('root_password', $this->rootPassword);
        \app\models\MinioSettings::setValue('mc_alias', $this->mcAlias);
        \app\models\MinioSettings::setValue('mc_alias_url', $this->mcAliasUrl);

        return "MinIO установлен и запущен.<br>Web-интерфейс: <a href=\"http://{$_SERVER['SERVER_ADDR']}:9000\" target=\"_blank\">http://{$_SERVER['SERVER_ADDR']}:9000</a>";
    }

    // Прочее — статус сервиса и т.д. (как выше)
    public function getServiceStatus()
    {
        $out = null;
        $service = $this->serviceName;
        exec("sudo systemctl status $service", $out);
        return $out ? implode("<br>", $out) : 'Не найден';
    }
}


