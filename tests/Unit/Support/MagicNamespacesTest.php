<?php

use Glugox\Magic\Support\MagicNamespaces;

test('magic namespaces resolve segments for models and providers', function () {
    MagicNamespaces::clear();

    expect(MagicNamespaces::models('User'))
        ->toBe('App\\Models\\User')
        ->and(MagicNamespaces::providers('ExampleServiceProvider'))
        ->toBe('App\\Providers\\ExampleServiceProvider');

    MagicNamespaces::use('Vendor\\Package');

    expect(MagicNamespaces::base())
        ->toBe('Vendor\\Package')
        ->and(MagicNamespaces::models('User'))
        ->toBe('Vendor\\Package\\Models\\User')
        ->and(MagicNamespaces::httpControllers('Api\\UserController'))
        ->toBe('Vendor\\Package\\Http\\Controllers\\Api\\UserController')
        ->and(MagicNamespaces::providers('MagicPackageServiceProvider'))
        ->toBe('Vendor\\Package\\Providers\\MagicPackageServiceProvider');

    MagicNamespaces::clear();
});
