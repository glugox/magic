<?php

use Glugox\Module\ModuleServiceProvider as RootModuleServiceProvider;
use Glugox\Module\Providers\ModuleServiceProvider as NamespacedModuleServiceProvider;

it('exposes the legacy provider namespace for backwards compatibility', function (): void {
    expect(class_exists(NamespacedModuleServiceProvider::class))->toBeTrue();
    expect(is_subclass_of(NamespacedModuleServiceProvider::class, RootModuleServiceProvider::class))->toBeTrue();
});
