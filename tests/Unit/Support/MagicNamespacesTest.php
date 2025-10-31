<?php

use Glugox\Magic\Support\MagicNamespaces;
use Glugox\ModelMeta\ModelMetaResolver;

function getModelMetaDefaultNamespace(): string
{
    $resolver = \Closure::bind(static function () {
        return ModelMetaResolver::$defaultNamespace;
    }, null, ModelMetaResolver::class);

    return $resolver();
}

test('magic namespaces resolve segments for models and providers', function () {
    if (! class_exists(ModelMetaResolver::class)) {
        test()->markTestSkipped('ModelMetaResolver is not available');
    }

    MagicNamespaces::clear();

    expect(MagicNamespaces::models('User'))
        ->toBe('App\\Models\\User')
        ->and(MagicNamespaces::providers('ExampleServiceProvider'))
        ->toBe('App\\Providers\\ExampleServiceProvider')
        ->and(getModelMetaDefaultNamespace())
        ->toBe('App\\Meta\\Models');

    MagicNamespaces::use('Vendor\\Package');

    expect(MagicNamespaces::base())
        ->toBe('Vendor\\Package')
        ->and(MagicNamespaces::models('User'))
        ->toBe('Vendor\\Package\\Models\\User')
        ->and(MagicNamespaces::httpControllers('Api\\UserController'))
        ->toBe('Vendor\\Package\\Http\\Controllers\\Api\\UserController')
        ->and(MagicNamespaces::providers('MagicPackageServiceProvider'))
        ->toBe('Vendor\\Package\\Providers\\MagicPackageServiceProvider')
        ->and(getModelMetaDefaultNamespace())
        ->toBe('Vendor\\Package\\Meta\\Models');

    MagicNamespaces::clear();

    expect(getModelMetaDefaultNamespace())->toBe('App\\Meta\\Models');
});
