#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/laravel-app"

# Default paths for local packages
DEFAULT_ORCHESTRATOR_PATH="/Users/ervin/Code/github.com/glugox/magic/packages/orchestrator"
DEFAULT_MODULE_PATH="/Users/ervin/Code/github.com/glugox/magic/packages/module"
DEFAULT_MAGIC_PATH="/Users/ervin/Code/github.com/glugox/magic"

LOCAL_ORCHESTRATOR_PATH="${ORCHESTRATOR_PATH:-$DEFAULT_ORCHESTRATOR_PATH}"
LOCAL_MODULE_PATH="${MODULE_PATH:-$DEFAULT_MODULE_PATH}"
LOCAL_MAGIC_PATH="${MAGIC_PATH:-$DEFAULT_MAGIC_PATH}"

# Validate paths exist
for pkg in LOCAL_ORCHESTRATOR_PATH LOCAL_MODULE_PATH LOCAL_MAGIC_PATH; do
    if [ ! -d "${!pkg}" ]; then
        echo "❌ Unable to locate local package for $pkg (expected at ${!pkg})." >&2
        exit 1
    fi
done

# Reset Laravel app
if [ -d "$APP_DIR" ]; then
    rm -rf "$APP_DIR"
fi

composer create-project laravel/laravel "$APP_DIR"

printf "Waiting for filesystem to settle...\n"
sleep 2

cd "$APP_DIR"

composer config minimum-stability dev
composer config prefer-stable true

# Inject local path repositories into composer.json
export LOCAL_ORCHESTRATOR_PATH LOCAL_MODULE_PATH LOCAL_MAGIC_PATH
php <<'PHP'
<?php
$composerFile = __DIR__ . '/composer.json';
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
];

$contents['repositories'] = array_merge($contents['repositories'] ?? [], $repos);

file_put_contents(
    $composerFile,
    json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
PHP

printf "Waiting for filesystem to settle before requiring packages...\n"
sleep 2

# Require orchestrator (which depends on module + magic)
composer require glugox/orchestrator:@dev
composer require --dev pestphp/pest --with-all-dependencies --prefer-stable

php artisan vendor:publish --tag=orchestrator-config --force

# Link specs folder
if [ -L "$APP_DIR/specs" ] || [ -e "$APP_DIR/specs" ]; then
    rm -rf "$APP_DIR/specs"
fi
ln -sfn "$SCRIPT_DIR/specs" "$APP_DIR/specs"

# Create demo Pest tests
mkdir -p tests/Feature

cat <<'PHP' > tests/Feature/BlogTest.php
<?php

use function Pest\Laravel\get;

it('loads the blog posts index', function () {
    get('/blog/posts')
        ->assertOk()
        ->assertSee('Posts');
});
PHP

cat <<'PHP' > tests/Feature/CrmTest.php
<?php

use function Pest\Laravel\get;

it('loads the CRM customers index', function () {
    get('/crm/customers')
        ->assertOk()
        ->assertSee('Customers');
});
PHP
