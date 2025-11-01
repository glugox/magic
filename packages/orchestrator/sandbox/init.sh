#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEMO_DIR="$SCRIPT_DIR/demo"
APP_DIR="$DEMO_DIR/laravel-app"
MODULES_DIR="$APP_DIR/modules"
MODULE_ROOT="$MODULES_DIR/demo/hello-world"
TEMPLATE_MODULE_ROOT="$SCRIPT_DIR/demo/modules/demo/hello-world"

DEFAULT_ORCHESTRATOR_PATH="$(realpath "$SCRIPT_DIR/..")"
DEFAULT_MODULE_PATH="$(realpath "$SCRIPT_DIR/../../module")"
DEFAULT_MAGIC_PATH="$(realpath "$SCRIPT_DIR/../../..")"

LOCAL_ORCHESTRATOR_PATH="${ORCHESTRATOR_PATH:-$DEFAULT_ORCHESTRATOR_PATH}"
LOCAL_MODULE_PATH="${MODULE_PATH:-$DEFAULT_MODULE_PATH}"
LOCAL_MAGIC_PATH="${MAGIC_PATH:-$DEFAULT_MAGIC_PATH}"

for pkg in LOCAL_ORCHESTRATOR_PATH LOCAL_MODULE_PATH LOCAL_MAGIC_PATH; do
    if [ ! -d "${!pkg}" ]; then
        echo "❌ Unable to locate local package for $pkg (expected at ${!pkg})." >&2
        exit 1
    fi
done

if [ ! -d "$TEMPLATE_MODULE_ROOT" ]; then
    echo "❌ Module template not found at $TEMPLATE_MODULE_ROOT." >&2
    exit 1
fi

if [ -d "$DEMO_DIR" ]; then
    rm -rf "$DEMO_DIR"
fi

mkdir -p "$DEMO_DIR"

composer create-project laravel/laravel "$APP_DIR"

printf "Waiting for filesystem to settle...\n"
sleep 2

cd "$APP_DIR"

composer config minimum-stability dev
composer config prefer-stable true

mkdir -p "$(dirname "$MODULE_ROOT")"
rm -rf "$MODULE_ROOT"
cp -R "$TEMPLATE_MODULE_ROOT" "$MODULE_ROOT"

export LOCAL_ORCHESTRATOR_PATH LOCAL_MODULE_PATH LOCAL_MAGIC_PATH MODULE_ROOT
php <<'PHP'
<?php
$composerFile = __DIR__.'/composer.json';
$contents = json_decode(file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

$repos = [
    'glugox-orchestrator-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_ORCHESTRATOR_PATH'),
        'options' => ['symlink' => true],
    ],
    'glugox-module-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_MODULE_PATH'),
        'options' => ['symlink' => true],
    ],
    'glugox-magic-local' => [
        'type' => 'path',
        'url' => getenv('LOCAL_MAGIC_PATH'),
        'options' => ['symlink' => true],
    ],
    'demo-hello-world-local' => [
        'type' => 'path',
        'url' => getenv('MODULE_ROOT'),
        'options' => ['symlink' => true],
    ],
];

$contents['repositories'] = array_merge($contents['repositories'] ?? [], $repos);

file_put_contents(
    $composerFile,
    json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
);
PHP

printf "Waiting for filesystem to settle before requiring packages...\n"
sleep 2

composer require glugox/orchestrator:@dev
composer require --dev pestphp/pest --with-all-dependencies --prefer-stable

php artisan vendor:publish --tag=orchestrator-config --force

if [ -L "$APP_DIR/specs" ] || [ -e "$APP_DIR/specs" ]; then
    rm -rf "$APP_DIR/specs"
fi
ln -sfn "$SCRIPT_DIR/specs" "$APP_DIR/specs"

cat <<'PHP' > tests/Feature/BlogTest.php
<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('loads the blog posts index', function (): void {
    get('/blog/posts')
        ->assertOk()
        ->assertSee('Posts');
});
PHP

cat <<'PHP' > tests/Feature/CrmTest.php
<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('loads the CRM customers index', function (): void {
    get('/crm/customers')
        ->assertOk()
        ->assertSee('Customers');
});
PHP

printf "Hello World module copied to %s\n" "$MODULE_ROOT"
