#!/bin/sh
set -e

# Clear any pre-existing cached configuration files to prevent boot crashes
echo "Clearing pre-existing cached files..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/events.php

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

# Start the command passed as arguments, or fallback to Supervisor
if [ $# -gt 0 ]; then
    echo "Running custom command: $@"
    exec "$@"
else
    echo "Starting Supervisor..."
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
