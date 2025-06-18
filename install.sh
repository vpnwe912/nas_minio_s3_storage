#!/bin/bash
set -e

echo "==== Update system ===="
sudo apt update && sudo apt upgrade -y

echo "==== Install utils ===="
sudo apt install -y curl wget unzip software-properties-common lsb-release ca-certificates apt-transport-https

echo "==== Add PHP 8.3 repository ===="
sudo apt install -y lsb-release ca-certificates apt-transport-https wget
sudo wget -O /etc/apt/trusted.gpg.d/sury.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
sudo apt update

echo "==== Install PHP 8.3 and extensions ===="
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-curl php8.3-xml php8.3-mbstring php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-soap php8.3-redis php8.3-ldap

echo "==== Install MariaDB ===="
sudo apt install -y mariadb-server

echo "==== Start and enable MariaDB ===="
sudo systemctl enable mariadb
sudo systemctl start mariadb

echo "==== Create database minio with utf8mb4 encoding ===="
sudo mysql -e "CREATE DATABASE IF NOT EXISTS minio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==== Install Nginx ===="
sudo apt install -y nginx

echo "==== Install Certbot ===="
sudo apt install -y certbot python3-certbot-nginx

echo "==== Install Composer ===="
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    echo 'Error: Invalid Composer signature'
    rm composer-setup.php
    exit 1
fi
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

echo "==== Check Composer ===="
export COMPOSER_ALLOW_SUPERUSER=1
echo "Composer version: $(composer --version)"

echo "==== Add nginx and php-fpm to autostart and start ===="
sudo systemctl enable nginx
sudo systemctl start nginx
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm

echo "==== Create a minio database with utf8mb4 encoding ===="
sudo mysql -e "CREATE DATABASE IF NOT EXISTS minio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==== MariaDB and minio database successfully created! ===="

echo "==== All dependencies installed! ===="
echo "PHP version: $(php -v | head -n 1)"
echo "MariaDB version: $(mariadb --version)"
echo "Nginx version: $(nginx -v 2>&1)"
echo "Composer version: $(composer --version)"
echo "============================="

# === Ввод домена и настройка nginx ===
read -p "Введите доменное имя для сайта (например, site.com): " DOMAIN

# Определяем путь к проекту (где лежит install.sh)
PROJECT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Создаём конфиг nginx для $DOMAIN"

NGINX_CONF="/etc/nginx/sites-available/$DOMAIN"
sudo tee $NGINX_CONF > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;

    root $PROJECT_PATH/web;
    index index.php index.html;

    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log  /var/log/nginx/${DOMAIN}_error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires max;
        log_not_found off;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Активируем сайт
sudo ln -sf $NGINX_CONF /etc/nginx/sites-enabled/$DOMAIN

# Проверяем конфиг и перезапускаем nginx
sudo nginx -t && sudo systemctl reload nginx

echo "Nginx сайт для $DOMAIN создан и активирован."

# === Установка SSL сертификата через certbot ===
echo "Запускаем Certbot для $DOMAIN"
sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos -m admin@$DOMAIN

echo "Let's Encrypt сертификат установлен для $DOMAIN"

echo "=== Всё готово! Nginx + SSL для $DOMAIN ==="
