#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/demo/laravel-app"

if [ ! -d "$APP_DIR" ]; then
    echo "Laravel app not found at $APP_DIR. Please run sandbox/init.sh first." >&2
    exit 1
fi

cd "$APP_DIR"

if php artisan list --raw | grep -q '^magic:build$'; then
    php artisan orchestrator:build demo/hello-world
else
    echo "Skipping orchestrator:build because magic:build command is not available."
fi

php artisan orchestrator:reload --no-cache
php artisan orchestrator:install demo/hello-world
php artisan migrate --force
php artisan route:list --path=api/hello-world
