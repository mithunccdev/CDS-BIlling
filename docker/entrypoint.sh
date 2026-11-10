#!/bin/sh
set -e

echo "================================================="
echo "   🚀 MS ERP - Starting Production Container     "
echo "================================================="

# Ensure directories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Fix ownership and permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Link public storage
if [ ! -L /var/www/html/public/storage ]; then
    echo "⚙ Linking public storage directory..."
    php artisan storage:link || true
fi

# Run Database Migrations and Seeds if enabled
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "⚙ Waiting for database connection at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    max_tries=30
    count=0
    until php -r "try { new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null || [ "$count" -ge "$max_tries" ]; do
        sleep 2
        count=$((count + 1))
        echo "⏳ Waiting for MySQL to be ready ($count/$max_tries)..."
    done

    echo "✔ Database connected! Running migrations..."
    php artisan migrate --force || echo "⚠ Warning: Database migration failed."

    if [ "${SEED_DATABASE:-false}" = "true" ]; then
        echo "🌱 Seeding default ERP demonstration dataset..."
        php artisan db:seed --force || echo "⚠ Warning: Database seeding failed."
    fi
fi

# Optimize application cache in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "⚡ Caching configurations, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "✔ Setup complete. Launching Supervisor (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
