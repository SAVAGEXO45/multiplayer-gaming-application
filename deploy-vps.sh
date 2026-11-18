#!/usr/bin/env bash
# ==============================================================================
# NEXUSARENA - Automated Ubuntu/Debian Cloud Server Deployment Script
# Tested on Ubuntu 22.04 LTS / 24.04 LTS (NairaHost Linux Cloud Server)
# ==============================================================================

set -e

echo "=== [1/6] Updating packages & installing system dependencies ==="
sudo apt-get update -y
sudo apt-get install -y git curl unzip zip software-properties-common nginx supervisor sqlite3 libsqlite3-dev

echo "=== [2/6] Adding PHP 8.3 PPA & installing PHP extensions ==="
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-sqlite3 php8.3-mysql \
    php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-gd php8.3-zip php8.3-intl

echo "=== [3/6] Installing Composer & Node.js ==="
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

echo "=== [4/6] Setting up project environment & dependencies ==="
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [ ! -f .env ]; then
    cp .env.example .env
fi

composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache database
touch database/database.sqlite

php artisan key:generate --force
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize

sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache database

echo "=== [5/6] Configuring Nginx Web Server ==="
NGINX_CONF="/etc/nginx/sites-available/nexusarena"
sudo tee "$NGINX_CONF" > /dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root $SCRIPT_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/nexusarena /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx
sudo systemctl restart php8.3-fpm

echo "=============================================================="
echo "  NEXUSARENA DEPLOYMENT COMPLETE!"
echo "  Your gaming website is live at: http://$(curl -s ifconfig.me)"
echo "=============================================================="
