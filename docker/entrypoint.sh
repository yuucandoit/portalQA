#!/bin/sh
set -e

echo "==> [QA Portal] Initializing environment..."

# 1. Essential runtime directories (Crucial for Alpine Nginx & Supervisor)
mkdir -p /run/nginx /var/log/nginx /var/log/supervisor /run

# 2. Environment file check & fallback
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.production.example ]; then
        echo "--> .env not found, copying from .env.production.example..."
        cp /var/www/html/.env.production.example /var/www/html/.env
    elif [ -f /var/www/html/.env.example ]; then
        echo "--> .env not found, copying from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    else
        echo "--> Creating new .env file..."
        touch /var/www/html/.env
    fi
fi

# 3. SQLite Database Preparation
DB_CONN=$(grep -E "^DB_CONNECTION=" /var/www/html/.env 2>/dev/null | cut -d '=' -f2 | tr -d ' ' || echo "sqlite")
if [ "$DB_CONN" = "sqlite" ] || [ -z "$DB_CONN" ]; then
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo "--> Initializing database/database.sqlite..."
        touch /var/www/html/database/database.sqlite
    fi
    # 777 on the SQLite directory is required so SQLite can write temporary -wal & -journal lock files
    chown -R www-data:www-data /var/www/html/database
    chmod -R 777 /var/www/html/database
fi

# 4. Storage & Cache Permissions
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Generate Application Key if empty
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "--> Generating application key..."
    php artisan key:generate --force || true
fi

# 6. Discover packages & create storage symlink
php artisan package:discover --ansi || true
php artisan storage:link --force || true

# 7. Run database migrations safely
echo "--> Running database migrations..."
php artisan migrate --force || echo "--> [Notice] Migrations finished with warning, continuing..."

# 8. Clear and optimize cache
echo "--> Optimizing configuration and view cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

if [ "$APP_ENV" = "production" ]; then
    php artisan view:cache || true
fi

echo "==> QA Portal is ready! Starting Supervisor (PHP-FPM, Nginx, Node.js Chat Server)..."

exec "$@"
