#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database to be ready (optional, adjust if needed)
echo "⏳ Waiting for database connection..."
php artisan db:show || echo "⚠️  Database not available yet, continuing..."

# Run migrations (if AUTO_MIGRATE is enabled)
if [ "${AUTO_MIGRATE}" = "true" ]; then
    echo "📦 Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Clear and cache configuration
echo "🔧 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "✅ Application ready!"

# Execute the main command (supervisord)
exec "$@"
