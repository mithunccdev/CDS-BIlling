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

# Run Database Self-Healing, Migrations and Seeds if enabled
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "⚙ Running database connection check and self-healing..."
    php /var/www/html/docker/heal-db.php || echo "⚠ Database heal script encountered an issue. Proceeding with migration..."

    echo "⚙ Running migrations..."
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
