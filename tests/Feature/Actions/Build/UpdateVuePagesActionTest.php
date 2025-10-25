<?php

use Glugox\Magic\Actions\Build\UpdateVuePagesAction;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->sidebarPath = base_path('resources/js/components/AppSidebar.vue');
    $this->appLogoPath = base_path('resources/js/components/AppLogo.vue');

    File::ensureDirectoryExists(dirname($this->sidebarPath));
    File::put($this->sidebarPath, <<<'VUE'
<script setup lang="ts">
import { Home } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    { title: 'Old', href: '/old', icon: Home },
];
</script>
VUE);

    File::ensureDirectoryExists(dirname($this->appLogoPath));
    File::put($this->appLogoPath, <<<'VUE'
<template>
    <span class="mb-0.5 truncate leading-tight font-semibold">OldApp</span>
</template>
VUE);
});

it('updates sidebar and app logo', function (): void {
    // Fake entity

    // Mock BuildContext
    $buildContext = getFixtureBuildContext();

    $action = new UpdateVuePagesAction();
    $action($buildContext);

    // ✅ Sidebar contains new entity
    expect(File::get($this->sidebarPath))
        ->toContain("title: 'Users'")
        ->toContain("href: '/users'");
    // ->toContain("icon: User");

    // ✅ Sidebar has updated lucide import
    /*expect($this->writtenFiles[$this->sidebarPath])
        ->toContain("import { User } from 'lucide-vue-next';");*/

    // ✅ AppLogo contains new app name
    expect(File::get($this->appLogoPath))
        ->toContain('>InventoryHub</span>');
});

it('scaffolds Vue components when generating into a package', function (): void {
    $packagePath = base_path('packages/demo-package');
    File::deleteDirectory($packagePath);

    $buildContext = getFixtureBuildContext();

    MagicPaths::usePackage($packagePath);

    try {
        $sidebarPath = MagicPaths::resource('js/components/AppSidebar.vue');
        $logoPath = MagicPaths::resource('js/components/AppLogo.vue');

        expect(File::exists($sidebarPath))->toBeFalse();
        expect(File::exists($logoPath))->toBeFalse();

        $action = new UpdateVuePagesAction();
        $action($buildContext);

        expect(File::exists($sidebarPath))->toBeTrue();
        expect(File::exists($logoPath))->toBeTrue();

        expect(File::get($sidebarPath))
            ->toContain("href: '/users'")
            ->toContain('const mainNavItems: NavItem[] = [');

        expect(File::get($logoPath))->toContain('>InventoryHub</span>');
    } finally {
        MagicPaths::clearPackage();
    }
});
