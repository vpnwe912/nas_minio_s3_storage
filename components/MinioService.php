<?php
namespace app\components;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Yii;

class MinioService
{
    /** @var S3Client */
    private $client;

    public function __construct(array $config = [])
    {
        // Параметры берём из .env
        $this->client = new S3Client([
            'endpoint'         => $_ENV['MINIO_HOST'],
            'region'           => 'us-east-1',          // любой, не важно для MinIO
            'version'          => 'latest',
            'use_path_style_endpoint' => true,          // обязательно для MinIO
            'credentials'      => [
                'key'    => $_ENV['MINIO_KEY'],
                'secret' => $_ENV['MINIO_SECRET'],
            ],
        ]);
    }

    /**
     * Список всех бакетов
     * @return array
     */
    public function listBuckets(): array
    {
        try {
            $result = $this->client->listBuckets();
            return $result->get('Buckets') ?: [];
        } catch (AwsException $e) {
            Yii::error('MinIO listBuckets error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Создать бакет
     */
    public function createBucket(string $name): bool
    {
        try {
            $this->client->createBucket(['Bucket' => $name]);
            return true;
        } catch (AwsException $e) {
            Yii::error('MinIO createBucket error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить бакет (он должен быть пуст)
     */
    public function deleteBucket(string $name): bool
    {
        try {
            $this->client->deleteBucket(['Bucket' => $name]);
            return true;
        } catch (AwsException $e) {
            Yii::error('MinIO deleteBucket error: ' . $e->getMessage());
            return false;
        }
    }

        /**
     * Список «папок» и объектов внутри бакета
     * @param string $bucket
     * @param string $prefix  (текущий префикс, например 'photos/2025/')
     * @return array ['folders'=>[], 'objects'=>[]]
     */
    public function listObjects(string $bucket, string $prefix = ''): array
    {
        try {
            $result = $this->client->listObjectsV2([
                'Bucket'    => $bucket,
                'Prefix'    => $prefix,
                'Delimiter' => '/',
            ]);
            $folders = $result->get('CommonPrefixes') ?: [];
            $objects = $result->get('Contents')       ?: [];
            return ['folders' => $folders, 'objects' => $objects];
        } catch (AwsException $e) {
            Yii::error('MinIO listObjects error: ' . $e->getMessage());
            return ['folders'=>[], 'objects'=>[]];
        }
    }

    /**
     * Создать «папку» (пустой объект с ключом ending '/')
     */
    public function createFolder(string $bucket, string $key): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key'    => rtrim($key, '/') . '/',
                'Body'   => '',
            ]);
            return true;
        } catch (AwsException $e) {
            Yii::error('MinIO createFolder error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Загрузить файл
     * @param string $bucket
     * @param string $key       // включая префикс, напр. 'photos/1.jpg'
     * @param string $source    // путь к локальному файлу
     */
    public function uploadFile(string $bucket, string $key, string $source): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $source,
            ]);
            return true;
        } catch (AwsException $e) {
            Yii::error('MinIO uploadFile error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить объект (или «папку» – удаляет пустой префикс)
     */
    public function deleteObject(string $bucket, string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            Yii::error('MinIO deleteObject error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Далее — методы для работы с объектами (будут в следующем этапе)
}
