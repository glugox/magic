<?php

use Glugox\Magic\Actions\Build\EnableAttachableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
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
