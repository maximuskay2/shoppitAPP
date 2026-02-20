#!/bin/sh
# Listen on all interfaces and Railway's PORT so the proxy can reach the app.
# Without 0.0.0.0 you get 502 (connection refused).

# Run from script directory (project root)
cd "$(dirname "$0")"

# Migrate so admins table exists, then ensure default admin (errors visible in Railway logs)
php artisan migrate --force --no-interaction 2>&1 || true
php artisan admin:ensure-one 2>&1 || true

PORT="${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
