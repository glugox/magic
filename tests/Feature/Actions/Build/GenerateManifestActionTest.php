<?php

use Glugox\Magic\Actions\Build\GenerateManifestAction;
use Glugox\Magic\Support\BuildContext;

beforeEach(function (): void {
    $this->contextMock = Mockery::mock(BuildContext::class);
});

it('writes manifest and returns context', function (): void {
    $this->contextMock->shouldReceive('writeManifest')->once();

    $action = new GenerateManifestAction;
    $result = $action($this->contextMock);

    expect($result)->toBe($this->contextMock);
});

it('handles exception during manifest writing gracefully', function (): void {
    $this->contextMock->shouldReceive('writeManifest')->andThrow(new Exception('Manifest error'));

    $action = new GenerateManifestAction;

    $exceptionThrown = false;
    try {
        $action($this->contextMock);
    } catch (Exception) {
        $exceptionThrown = true;
    }

    expect($exceptionThrown)->toBeTrue();
});
