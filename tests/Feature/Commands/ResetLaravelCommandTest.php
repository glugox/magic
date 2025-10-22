<?php

use Illuminate\Support\Facades\File;

it('cleans generated ui resources when resetting laravel files', function (): void {
    $stubPath = realpath(__DIR__.'/../../../stubs/laravel');

    expect($stubPath)->not->toBeFalse();

    File::copyDirectory($stubPath, base_path());

    // Simulate generated shadcn components and filters
    File::ensureDirectoryExists(base_path('resources/js/components/ui'));
    File::put(base_path('resources/js/components/ui/table.vue'), '<template></template>');

    File::ensureDirectoryExists(base_path('resources/js/components/filters'));
    File::put(base_path('resources/js/components/filters/ExtraFilter.vue'), '<template></template>');

    // Other directories that should remain untouched (e.g. vendor)
    File::ensureDirectoryExists(base_path('vendor/magic'));
    File::put(base_path('vendor/magic/keep.txt'), 'keep');

    $this->artisan('magic:reset-laravel')->assertSuccessful();

    expect(is_dir(base_path('resources/js/components/ui')))->toBeFalse();
    expect(File::exists(base_path('resources/js/components/filters/ExtraFilter.vue')))->toBeFalse();

    expect(File::exists(base_path('resources/js/components/AppLogo.vue')))->toBeTrue();
    expect(File::exists(base_path('resources/js/components/NavMain.vue')))->toBeTrue();

    expect(is_dir(base_path('vendor/magic')))->toBeTrue();
    expect(File::exists(base_path('vendor/magic/keep.txt')))->toBeTrue();
});
