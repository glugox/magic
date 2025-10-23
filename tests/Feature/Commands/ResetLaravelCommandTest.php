<?php

use Glugox\Magic\Actions\Files\BackupOriginalFileAction;
use Illuminate\Support\Facades\File;

it('restores backed up files and removes generated ui resources when resetting laravel files', function (): void {
    File::deleteDirectory(base_path('.magic'));

    File::ensureDirectoryExists(base_path('resources/js/components'));
    File::put(base_path('resources/js/components/AppLogo.vue'), '<template>original</template>');
    File::put(base_path('resources/js/components/NavMain.vue'), '<template>original</template>');

    app(BackupOriginalFileAction::class)(base_path('resources/js/components/AppLogo.vue'));
    app(BackupOriginalFileAction::class)(base_path('resources/js/components/NavMain.vue'));

    File::put(base_path('resources/js/components/AppLogo.vue'), '<template>modified</template>');

    File::ensureDirectoryExists(base_path('resources/js/components/ui'));
    File::put(base_path('resources/js/components/ui/table.vue'), '<template></template>');

    File::ensureDirectoryExists(base_path('resources/js/components/filters'));
    File::put(base_path('resources/js/components/filters/ExtraFilter.vue'), '<template></template>');

    File::ensureDirectoryExists(base_path('vendor/magic'));
    File::put(base_path('vendor/magic/keep.txt'), 'keep');

    $this->artisan('magic:reset-laravel')->assertSuccessful();

    expect(is_dir(base_path('resources/js/components/ui')))->toBeFalse();
    expect(File::exists(base_path('resources/js/components/filters/ExtraFilter.vue')))->toBeFalse();

    expect(File::get(base_path('resources/js/components/AppLogo.vue')))->toBe('<template>original</template>');
    expect(File::get(base_path('resources/js/components/NavMain.vue')))->toBe('<template>original</template>');

    expect(is_dir(base_path('vendor/magic')))->toBeTrue();
    expect(File::exists(base_path('vendor/magic/keep.txt')))->toBeTrue();
});
