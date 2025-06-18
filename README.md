# Управление MinIO

Веб-интерфейс для управления MinIO сервером, построенный на Yii2.

## Требования

- PHP 7.4 или выше
- MySQL 5.7 или выше / MariaDB 10.2 или выше
- Composer
- Node.js и NPM (для ассетов)
- MinIO сервер

## Установка

1. **Клонирование репозитория**
   ```bash
   git clone [ваш-репозиторий] minio-manager
   cd minio-manager
   ```

2. **Установка зависимостей**
   ```bash
   composer install
   ```

3. **Настройка окружения**
   - Создайте копию файла `.env.example` и назовите его `.env`
   - Настройте параметры подключения к БД и MinIO в файле `.env`:
     ```
     DB_DSN=mysql:host=localhost;port=3306;dbname=minio_manager
     DB_USER=ваш_пользователь
     DB_PASS=ваш_пароль
     
     MINIO_ENDPOINT=minio.example.com
     MINIO_ACCESS_KEY=ваш_access_key
     MINIO_SECRET_KEY=ваш_secret_key
     MINIO_USE_SSL=false
     MINIO_REGION=us-east-1
     ```

4. **Настройка прав доступа**
   ```bash
   chmod -R 777 runtime/
   chmod -R 777 web/assets/
   ```

5. **Инициализация приложения**
   ```bash
   php init --env=Development --overwrite=All
   ```

6. **Скачать бинарные файлы**
   ```bash
   php yii binaries/download-all
   ```

7. **Применение миграций**
   ```bash
   php yii migrate --interactive=0
   ```

8. **Настройка веб-сервера**
   Настройте веб-сервер (например, Nginx или Apache) на директорию `web/`

## Настройка Nginx

Пример конфигурации для Nginx:

```nginx
server {
    listen 80;
    server_name minio-manager.example.com;
    root /path/to/minio-manager/web;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        try_files $uri =404;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Использование

### Основные команды

- **Применение миграций**
  ```bash
  php yii migrate
  ```

- **Создание нового контроллера**
  ```bash
  php yii gii/controller --controllerClass=app\\controllers\\NewController
  ```

- **Создание новой модели**
  ```bash
  php yii gii/model --tableName=table_name --modelClass=NewModel
  ```

- **Запуск тестов**
  ```bash
  php vendor/bin/codecept run
  ```

## Доступ к панели управления

После установки откройте в браузере адрес вашего веб-сервера. 

По умолчанию создается административный пользователь:
- Логин: admin@example.com
- Пароль: admin123

**Важно!** Не забудьте изменить пароль администратора после первого входа.

## Разработка

### Структура проекта

- `controllers/` - Контроллеры приложения
- `models/` - Модели данных
- `views/` - Представления
- `components/` - Компоненты приложения
- `config/` - Конфигурационные файлы
- `migrations/` - Миграции базы данных
- `web/` - Веб-корень приложения

### Поддержка

По вопросам и предложениям обращайтесь в раздел Issues.

## Лицензия

[Указать лицензию]

## В PHP (php.ini):
upload_max_filesize = 100M
post_max_size = 100M

## В nginx.conf:
client_max_body_size 100M;