#!/bin/sh
set -e

echo "==> Starting QA Portal initialization..."

# 1. Environment file check
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.example ]; then
        echo "--> .env not found, copying from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# 2. SQLite Database Preparation (Default out-of-the-box)
DB_CONN=$(grep -E "^DB_CONNECTION=" /var/www/html/.env 2>/dev/null | cut -d '=' -f2 | tr -d ' ' || echo "sqlite")
if [ "$DB_CONN" = "sqlite" ] || [ -z "$DB_CONN" ]; then
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo "--> Initializing database/database.sqlite..."
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

# 3. Storage & Cache Permissions
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Generate Application Key if not present
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "--> Generating application key..."
    php artisan key:generate --force
fi

# Discover packages & dump optimized autoloader
php artisan package:discover --ansi || true

# 5. Create storage symlink
php artisan storage:link --force || true

# 6. Run database migrations
echo "--> Running database migrations..."
php artisan migrate --force

# 7. Optimize cache in production
if [ "$APP_ENV" = "production" ]; then
    echo "--> Caching routes and views for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> QA Portal is ready! Starting Supervisor services (PHP-FPM, Nginx, Node.js Chat Server)..."

exec "$@"
