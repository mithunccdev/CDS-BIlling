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

# If APP_KEY is missing, generate one
if [ -z "$APP_KEY" ]; then
    echo "⚙ APP_KEY not provided. Generating new APP_KEY..."
    php artisan key:generate --force
fi

# Link public storage
if [ ! -L /var/www/html/public/storage ]; then
    echo "⚙ Linking public storage directory..."
    php artisan storage:link || true
fi

# Run Database Migrations and Seeds if enabled
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "⚙ Waiting for database connection and running migrations..."
    php artisan migrate --force || echo "⚠ Warning: Database migration failed. Check DB credentials."

    if [ "${SEED_DATABASE:-false}" = "true" ]; then
        echo "🌱 Seeding default ERP demonstration dataset..."
        php artisan db:seed --force || echo "⚠ Warning: Database seeding failed."
    fi
fi

# Optimize application cache in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "⚡ Caching routes, configurations, and blade views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "✔ Setup complete. Launching Supervisor (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

