<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Define which paths Rector should refactor
    $rectorConfig->paths([
        __DIR__.'/stubs',
        __DIR__.'/tests',
    ]);

    // Skip vendor and storage
    $rectorConfig->skip([
        __DIR__.'/vendor',
        __DIR__.'/storage',
        __DIR__.'/bootstrap/cache',
        __DIR__.'/node_modules',
        __DIR__.'/public',
        __DIR__.'/build',
        __DIR__.'/app',
        __DIR__.'/config',
    ]);

    // Use Laravel-specific rules
    $rectorConfig->sets([
        LaravelSetList::LARAVEL_90, // or 100, 110 depending on your target
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        LevelSetList::UP_TO_PHP_81, // adjust based on your PHP version
        LaravelSetList::LARAVEL_IF_HELPERS
    ]);

    // Auto-import fully qualified names (nice cleanup)
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
