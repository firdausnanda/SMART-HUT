#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Validate critical environment variables before booting
# ---------------------------------------------------------------------------

# LOG_CHANNEL must not be empty — an empty value causes "Log [] is not defined"
if [ -z "$LOG_CHANNEL" ]; then
    echo "[WARNING] LOG_CHANNEL is not set or empty. Defaulting to 'daily'."
    export LOG_CHANNEL=daily
fi

# Check that the PHP zip extension is available (required by spatie/laravel-backup)
if ! php -m | grep -q "^zip$"; then
    echo "[WARNING] PHP zip extension is not loaded. Backup/import features may not work."
fi

# ---------------------------------------------------------------------------
# Clear any pre-existing cached configuration files to prevent boot crashes
# ---------------------------------------------------------------------------
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

# ---------------------------------------------------------------------------
# Prune stale queue data to keep the database clean
# ---------------------------------------------------------------------------

# Remove failed jobs older than 7 days (168 hours)
echo "Pruning old failed jobs..."
php artisan queue:prune-failed --hours=168 2>/dev/null || true

# Prune completed/cancelled job batches older than 7 days (168 hours)
echo "Pruning old job batches..."
php artisan queue:prune-batches --hours=168 2>/dev/null || true

# ---------------------------------------------------------------------------
# Start the command passed as arguments, or fallback to Supervisor
# ---------------------------------------------------------------------------
if [ $# -gt 0 ]; then
    echo "Running custom command: $@"
    exec "$@"
else
    echo "Starting Supervisor..."
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
