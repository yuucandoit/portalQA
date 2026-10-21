#!/bin/sh
set -e

echo "==> [QA Portal] Initializing environment..."

# 0. Clean stale local cache files (e.g. dev service providers like Pail)
rm -f /var/www/html/bootstrap/cache/*.php

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

# 2.5 Ensure migrations and seeders are always up-to-date even if /var/www/html/database is a mounted volume
if [ -d /var/www/html/database_src/migrations ]; then
    echo "--> Syncing latest database migrations and seeders from image..."
    mkdir -p /var/www/html/database/migrations /var/www/html/database/seeders /var/www/html/database/factories
    cp -f /var/www/html/database_src/migrations/* /var/www/html/database/migrations/ 2>/dev/null || true
    cp -f /var/www/html/database_src/seeders/* /var/www/html/database/seeders/ 2>/dev/null || true
    cp -f /var/www/html/database_src/factories/* /var/www/html/database/factories/ 2>/dev/null || true
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

# 8. Run initial database seeders if database has no users yet
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "--> Database is empty. Running initial database seeders (Users & Default Channels)..."
    php artisan db:seed --force || true
fi

# 9. Clear and optimize cache
echo "--> Optimizing configuration and view cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

if [ "$APP_ENV" = "production" ]; then
    php artisan view:cache || true
fi

echo "==> QA Portal is ready! Starting Supervisor (PHP-FPM, Nginx, Node.js Chat Server)..."

exec "$@"
