<?php

use Glugox\Magic\Actions\Build\UpdateVuePagesAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    // Fake filesystem
    File::partialMock()->shouldReceive('exists')->andReturn(true);

    // Create fake Vue files
    $this->sidebarPath = base_path('resources/js/components/AppSidebar.vue');
    $this->appLogoPath = base_path('resources/js/components/AppLogo.vue');

    File::partialMock()->shouldReceive('get')->with($this->sidebarPath)->andReturn(
        <<<'VUE'
<script setup lang="ts">
import { Home } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    { title: 'Old', href: '/old', icon: Home },
];
</script>
VUE
    );

    File::partialMock()->shouldReceive('get')->with($this->appLogoPath)->andReturn(
        <<<'VUE'
<template>
    <span class="mb-0.5 truncate leading-tight font-semibold">OldApp</span>
</template>
VUE
    );

    // Fake GenerateFileAction (don't actually write)
    app()->bind(GenerateFileAction::class, fn (): Closure => function ($path, $content): void {
        $this->writtenFiles[$path] = $content;
    });

    $this->writtenFiles = [];
});

it('updates sidebar and app logo', function (): void {
    // Fake entity

    // Mock BuildContext
    $buildContext = getFixtureBuildContext();

    $action = new UpdateVuePagesAction();
    $action($buildContext);

    // ✅ Sidebar contains new entity
    expect($this->writtenFiles[$this->sidebarPath])
        ->toContain("title: 'Users'")
        ->toContain("href: '/users'");
    // ->toContain("icon: User");

    // ✅ Sidebar has updated lucide import
    /*expect($this->writtenFiles[$this->sidebarPath])
        ->toContain("import { User } from 'lucide-vue-next';");*/

    // ✅ AppLogo contains new app name
    expect($this->writtenFiles[$this->appLogoPath])
        ->toContain('>InventoryHub</span>');
});
