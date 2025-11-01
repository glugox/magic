#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"

if [ ! -d "$APP_DIR" ]; then
    echo "Laravel app not found at $APP_DIR. Please run sandbox/init.sh first." >&2
    exit 1
fi

cd "$APP_DIR"

php artisan route:list --name=contacts || true

echo ""
echo "Sandbox ready. To serve the application run:"
echo "  cd $APP_DIR"
echo "  php artisan serve"
echo "Then open http://127.0.0.1:8000/contacts"
