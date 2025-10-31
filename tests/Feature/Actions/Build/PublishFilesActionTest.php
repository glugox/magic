<?php

use Glugox\Magic\Actions\Build\PublishFilesAction;
use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/*beforeEach(function (): void {
    // Fake logs
    Log::spy();
    $fakeLogger = Mockery::mock(LoggerInterface::class);
    $fakeLogger->shouldReceive('info')->byDefault();

    Log::shouldReceive('channel')
        ->with('magic')
        ->andReturn($fakeLogger);

    // Fake filesystem
    File::shouldReceive('ensureDirectoryExists')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();

    // Mock CopyDirectoryAction
    $this->copyDirectoryMock = Mockery::mock(CopyDirectoryAction::class);
    $this->copyDirectoryMock->shouldReceive('__invoke')
        ->andReturn(['file1.ts', 'file2.ts']);
    $this->app->instance(CopyDirectoryAction::class, $this->copyDirectoryMock);

    // Mock GenerateFileAction
    $this->generateFileMock = Mockery::mock(GenerateFileAction::class);
    $this->generateFileMock->shouldReceive('__invoke')
        ->andReturnTrue();
    $this->app->instance(GenerateFileAction::class, $this->generateFileMock);

    // Build context with a sample config
    $this->context = $this->createBuildContextFromFile('callcenter.json');
    $this->action = app(PublishFilesAction::class);
});*/

/*test('it publishes files and generates support files', function (): void {
    $result = ($this->action)($this->context);

    expect($result)->toBeInstanceOf(BuildContext::class);

    // Assert CopyDirectoryAction was called
    $this->copyDirectoryMock->shouldHaveReceived('__invoke')->once();

    // Assert GenerateFileAction was called
    $this->generateFileMock->shouldHaveReceived('__invoke')->atLeast()->once();
});*/

test('it publishes files and generates support files', function (): void {
    $action = app(PublishFilesAction::class);
    $context = $this->createBuildContextFromFile('callcenter.json');
    $result = ($action)($context);

    expect($result)->toBeInstanceOf(BuildContext::class);
});

test('it copies support scaffolding when building a package', function (): void {
    $configPath = __DIR__.'/../../data/callcenter.json';
    $config = Config::fromJsonFile($configPath);

    $packagePath = base_path('packages/magic-package-'.uniqid());
    File::deleteDirectory($packagePath);

    $context = BuildContext::fromOptions([
        'config' => $configPath,
        'package-path' => $packagePath,
        'package-namespace' => 'Vendor\\Package',
        'package-name' => 'vendor/package',
    ])->setConfig($config);

    MagicNamespaces::use('Vendor\\Package');
    MagicPaths::usePackage($packagePath);

    try {
        $action = app(PublishFilesAction::class);
        $action($context);

        expect(File::exists(MagicPaths::app('Traits/HasName.php')))->toBeFalse();
        expect(File::exists(MagicPaths::app('Http/Responses/ApiResponse.php')))->toBeFalse();
        expect(File::exists(MagicPaths::app('Http/Middleware/HandleInertiaRequests.php')))->toBeFalse();

        expect(class_exists(\Glugox\Module\Eloquent\HasName::class))->toBeTrue();
        expect(class_exists(\Glugox\Module\Http\Responses\ApiResponse::class))->toBeTrue();
        expect(class_exists(\Glugox\Module\Http\Middleware\HandleInertiaRequests::class))->toBeTrue();

        expect(File::exists(MagicPaths::resource('js/components/AppSidebar.vue')))->toBeTrue();
        expect(File::exists(MagicPaths::resource('views/app.blade.php')))->toBeTrue();
        expect(File::exists(MagicPaths::base('scripts/generate-ui-index.mjs')))->toBeTrue();
        expect(File::exists(MagicPaths::tests('Fixtures/test_file.txt')))->toBeTrue();
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
        File::deleteDirectory($packagePath);
    }
});
