<?php

use Glugox\Magic\Helpers\EnvHelper;

beforeEach(function () {
    // Copy the provided .env content into a temp file for testing
    $this->envPath = __DIR__ . '/.env.testing';

    $content = <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:eiq2xLX4Mtnw0CEYYLaVrY7qGRvydJIHj9O0VQRh+w4=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"
ENV;

    file_put_contents($this->envPath, $content);
});

afterEach(function () {
    unlink($this->envPath);
});

test('it updates an existing variable', function () {
    EnvHelper::setEnvValue('APP_ENV', 'production', $this->envPath);

    $content = file_get_contents($this->envPath);
    expect($content)->toMatch('/^APP_ENV=production/m');
    expect(substr_count($content, 'APP_ENV='))->toBe(1);
});

test('it appends a new variable', function () {
    EnvHelper::setEnvValue('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1', $this->envPath);

    $content = file_get_contents($this->envPath);
    //expect($content)->toMatch('/SANCTUM_STATEFUL_DOMAINS="localhost,127.0.0.1"/');
    expect($content)->toMatch('/SANCTUM_STATEFUL_DOMAINS=localhost,127\.0\.0\.1/');
});

test('it updates a quoted value correctly', function () {
    EnvHelper::setEnvValue('MAIL_FROM_ADDRESS', 'admin@example.com', $this->envPath);

    $content = file_get_contents($this->envPath);
    // Allow either MAIL_FROM_ADDRESS=admin@example.com OR MAIL_FROM_ADDRESS="admin@example.com"
    expect($content)->toMatch('/^MAIL_FROM_ADDRESS="?admin@example.com"?$/m');

    expect(substr_count($content, 'MAIL_FROM_ADDRESS='))->toBe(1);
});

test('it preserves other variables and only changes target', function () {
    EnvHelper::setEnvValue('SESSION_DRIVER', 'redis', $this->envPath);

    $content = file_get_contents($this->envPath);

    // Only SESSION_DRIVER is changed
    expect($content)->toMatch('/^SESSION_DRIVER=redis/m');
    expect($content)->toMatch('/^APP_NAME=Laravel/m'); // untouched
});

test('it handles values with spaces by quoting them', function () {
    EnvHelper::setEnvValue('APP_NAME', 'My Laravel App', $this->envPath);

    $content = file_get_contents($this->envPath);
    expect($content)->toMatch('/^APP_NAME="My Laravel App"/m');
});

test('it handles adding sanctum session config', function () {
    EnvHelper::setEnvValue('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1', $this->envPath);
    EnvHelper::setEnvValue('SESSION_DRIVER', 'database', $this->envPath);
    EnvHelper::setEnvValue('SESSION_DOMAIN', 'localhost', $this->envPath);

    $content = file_get_contents($this->envPath);

    expect($content)->toMatch('/^SANCTUM_STATEFUL_DOMAINS="?localhost,127\.0\.0\.1"?$/m');
    expect($content)->toMatch('/^SESSION_DRIVER=database/m');
    expect($content)->toMatch('/^SESSION_DOMAIN=localhost/m');
});

