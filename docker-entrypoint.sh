#!/bin/sh

# Cek apakah direktori storage sudah ada, jika belum buat

echo "[Entrypoint] Setting up Laravel environment..."


mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/testing
mkdir -p /var/www/storage/framework/views

mkdir -p /var/www/storage/logs
# mkdir -p /var/www/storage/{app/public,framework/{cache/data,sessions,testing,views},logs}
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/www/bootstrap/cache

# Set permissions pada folder storage dan cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Buat file log jika belum ada dan set permissions
# touch /var/www/storage/logs/{laravel.log,websockets.log,queue.log,swoole.log,supervisord.log}
touch /var/www/storage/logs/laravel.log
touch /var/www/storage/logs/websockets.log
chmod 664 /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/websockets.log
# Permission
# chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache
# chmod -R 775 /var/www/storage /var/www/bootstrap/cache
# chmod 664 /var/www/storage/logs/*.log


# Jalankan composer install kalau vendor belum ada
if [ ! -d "/var/www/vendor" ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate key kalau belum ada .env atau APP_KEY
if [ ! -f "/var/www/.env" ]; then
  cp /var/www/.env.example /var/www/.env
fi

if ! grep -q "^APP_KEY=" /var/www/.env || grep -q "APP_KEY=$" /var/www/.env; then
  php artisan key:generate
fi


# php artisan config:cache
# php artisan route:cache




# Jalankan Supervisor untuk menanggani queue, websockets, dan swoole
echo "Starting Supervisor..."
if ! [ -x "$(command -v /usr/bin/supervisord)" ]; then
  echo 'Error: supervisord is not installed.' >&2
  exit 1
fi

/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Pastikan supervisord berjalan, kemudian jalankan PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
