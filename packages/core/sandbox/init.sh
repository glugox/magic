#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"
CONFIG_FILE="$SCRIPT_DIR/configs/builder-demo.json"

REPO_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"

DEFAULT_CORE_PATH="$REPO_ROOT/packages/core"
DEFAULT_BUILDER_PATH="$REPO_ROOT/packages/builder"
DEFAULT_MAGIC_PATH="$REPO_ROOT"

LOCAL_CORE_PATH="${CORE_PATH:-$DEFAULT_CORE_PATH}"
LOCAL_BUILDER_PATH="${BUILDER_PATH:-$DEFAULT_BUILDER_PATH}"
LOCAL_MAGIC_PATH="${MAGIC_PATH:-$DEFAULT_MAGIC_PATH}"

for pkg in LOCAL_CORE_PATH LOCAL_BUILDER_PATH LOCAL_MAGIC_PATH; do
    if [ ! -d "${!pkg}" ]; then
        echo "❌ Unable to locate local package for $pkg (expected at ${!pkg})." >&2
        exit 1
    fi
done

if [ -d "$APP_DIR" ]; then
    rm -rf "$APP_DIR"
fi

composer create-project laravel/laravel "$APP_DIR"

sleep 2

cd "$APP_DIR"

composer config minimum-stability dev
composer config prefer-stable true

export LOCAL_CORE_PATH LOCAL_BUILDER_PATH LOCAL_MAGIC_PATH
php <<'PHP'
<?php
$composerFile = __DIR__ . '/composer.json';
$contents = json_decode(file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

$repos = [
    'glugox-core-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_CORE_PATH'),
        'options' => ['symlink' => true],
    ],
    'glugox-builder-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_BUILDER_PATH'),
        'options' => ['symlink' => true],
    ],
    'glugox-magic-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_MAGIC_PATH'),
        'options' => ['symlink' => true],
    ],
];

$contents['repositories'] = array_merge($contents['repositories'] ?? [], $repos);

file_put_contents(
    $composerFile,
    json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
PHP

composer require glugox/core:@dev glugox/builder:@dev

php artisan vendor:publish --tag=core-config --force

MODULE_DIR="$APP_DIR/modules/demo-module"

php artisan builder:generate --config="$CONFIG_FILE" --package-path="$MODULE_DIR"

cat <<'PHP' > config/core.php
<?php

return [
    'modules' => [
        'demo' => [
            'path' => base_path('modules/demo-module'),
            'routes' => [
                'routes/api.php',
            ],
        ],
    ],
];
PHP

php artisan optimize:clear

printf '\n✨ Core sandbox is ready.\n'
printf 'Laravel application path: %s\n' "$APP_DIR"
printf 'Run "cd %s && php artisan serve" to start the dev server.\n' "$APP_DIR"
printf 'Then open http://127.0.0.1:8000/contacts to view the demo module response.\n'
