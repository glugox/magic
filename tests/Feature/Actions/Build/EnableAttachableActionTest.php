<?php

use Glugox\Magic\Actions\Build\EnableAttachableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    // Mock filesystem methods
    File::shouldReceive('exists')->andReturn(false);
    File::shouldReceive('is_dir')->andReturn(false);
    File::shouldReceive('mkdir')->andReturnTrue();
    File::shouldReceive('copy')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();

    // Mock logging
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->andReturnNull();
});

it('skips when no entities have images', function (): void {
    $configMock = Mockery::mock(Config::class);
    $configMock->shouldReceive('anyEntityHasImages')->andReturn(false);

    $contextMock = Mockery::mock(BuildContext::class);
    $contextMock->shouldReceive('getConfig')->andReturn($configMock);

    $action = app(EnableAttachableAction::class);
    $result = $action($contextMock);

    expect($result)->toBe($contextMock);
});

it('copies files and includes attachable routes', function (): void {
    $configMock = Mockery::mock(Config::class);
    $configMock->shouldReceive('anyEntityHasImages')->andReturn(true);

    $contextMock = Mockery::mock(BuildContext::class);
    $contextMock->shouldReceive('getConfig')->andReturn($configMock);

    $action = Mockery::mock(EnableAttachableAction::class)->makePartial();
    $action->shouldAllowMockingProtectedMethods();
    $action->shouldReceive('copyFile')->andReturnNull(); // skip actual file copying
    $action->shouldReceive('includeAttachableRoutes')->andReturnNull();

    $result = $action($contextMock);

    expect($result)->toBe($contextMock);
});

it('relies on vendor scaffolding when building a package', function (): void {
    $config = $this->createConfigFromFile('callcenter.json');
    $context = BuildContext::fromOptions([
        'config' => __DIR__.'/../../data/callcenter.json',
        'package-path' => base_path('modules/test-package-'.uniqid()),
        'package-namespace' => 'Vendor\\Package',
        'package-name' => 'vendor/package',
    ])->setConfig($config);

    MagicNamespaces::use('Vendor\\Package');
    $packagePath = $context->getDestinationPath();
    expect($packagePath)->not->toBeNull();
    MagicPaths::usePackage($packagePath);

    try {
        $copied = [];

        $action = Mockery::mock(EnableAttachableAction::class)->makePartial();
        $action->shouldAllowMockingProtectedMethods();
        $action->shouldReceive('copyFile')
            ->andReturnUsing(function (array $file) use (&$copied): void {
                $copied[] = $file['src'];
            });
        $action->shouldReceive('includeAttachableRoutes')->never();

        $action($context);

        expect($copied)
            ->toContain('migration/create_attachments_table.php')
            ->and($copied)->toContain('api-resources/AttachmentResource.php')
            ->and($copied)->toContain('controllers/AttachmentController.php')
            ->and($copied)->not->toContain('traits/HasImages.php')
            ->and($copied)->not->toContain('models/Attachment.php')
            ->and($copied)->not->toContain('jobs/ProcessAttachment.php')
            ->and($copied)->not->toContain('routes/attachable.php')
            ->and($copied)->not->toContain('config/attachments.php');
    } finally {
        MagicPaths::clearPackage();
        MagicNamespaces::clear();
    }
});
