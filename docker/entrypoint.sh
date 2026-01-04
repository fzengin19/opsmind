#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
max_attempts=30
attempt=0

until php artisan db:show > /dev/null 2>&1 || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "   Database not ready yet (attempt $attempt/$max_attempts)..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "⚠️  Warning: Could not connect to database after ${max_attempts} attempts"
    echo "   Continuing anyway... (migrations may fail)"
fi

# Run migrations (if AUTO_MIGRATE is enabled)
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "📦 Running database migrations..."
    php artisan migrate --force --no-interaction || {
        echo "❌ Migration failed! Check database connection."
        exit 1
    }
    echo "✅ Migrations completed successfully"
else
    echo "⏭️  Skipping migrations (AUTO_MIGRATE not enabled)"
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
