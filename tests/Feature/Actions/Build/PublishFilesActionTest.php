<?php

use Glugox\Magic\Actions\Build\PublishFilesAction;
use Glugox\Magic\Actions\Files\CopyDirectoryAction;
use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\TypeHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

beforeEach(function () {
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
    $this->context = getFixtureBuildContext();
    $this->action = app(PublishFilesAction::class);
});

test('it publishes files and generates support files', function () {
    $result = ($this->action)($this->context);

    expect($result)->toBeInstanceOf(BuildContext::class);

    // Assert CopyDirectoryAction was called
    $this->copyDirectoryMock->shouldHaveReceived('__invoke')->once();

    // Assert GenerateFileAction was called
    $this->generateFileMock->shouldHaveReceived('__invoke')->atLeast()->once();
});
