#!/bin/sh
set -e

# Cache configuration, routes, and views for production performance
echo "Caching Laravel configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure the storage link exists
echo "Creating storage link..."
php artisan storage:link --force

# Run database migrations if RUN_MIGRATIONS env var is set to true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Start Supervisor (which runs Nginx and PHP-FPM)
echo "Starting Supervisor..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
