#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"

if [ ! -d "$APP_DIR" ]; then
    echo "Laravel app not found at $APP_DIR. Please run sandbox/init.sh first." >&2
    exit 1
fi

cd "$APP_DIR"

php artisan orchestrator:build
#php artisan orchestrator:install glugox/blog
php artisan orchestrator:install glugox/crm
php artisan migrate --force
#./vendor/bin/pest


