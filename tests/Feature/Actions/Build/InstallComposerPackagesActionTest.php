<?php

use Glugox\Magic\Actions\Build\InstallComposerPackagesAction;
use Glugox\Magic\Support\BuildContext;

beforeEach(function () {
    // Create a dummy BuildContext
    $this->context = new BuildContext();
});

it('runs composer installation for missing packages', function () {
    $action = $this->partialMock(InstallComposerPackagesAction::class, function ($mock) {
        // Override the runProcess method to not actually execute composer
        $mock->shouldAllowMockingProtectedMethods()
            ->makePartial()
            ->shouldReceive('runProcess')
            ->once()
            ->withArgs(function ($command, $message) {
                expect($command[1])->toBe('require'); // composer require
                expect(str_contains($command[2], 'glugox/model-meta'))->toBeTrue();

                return true;
            });
        // Mock isComposerPackageInstalled to return false, forcing installation
        $mock->shouldReceive('isComposerPackageInstalled')
            ->andReturnFalse();
    });

    $action($this->context);
});

it('skips installation if package is already installed', function () {
    $action = $this->partialMock(InstallComposerPackagesAction::class, function ($mock) {
        $mock->shouldAllowMockingProtectedMethods()
            ->makePartial()
            ->shouldNotReceive('runProcess');
        // Simulate package already installed
        $mock->shouldReceive('isComposerPackageInstalled')
            ->andReturnTrue();
    });

    $action($this->context);
});

it('installs composer packages and logs appropriately', function () {
    $context = getFixtureBuildContext();

    $action = new InstallComposerPackagesAction();
    $action($context);
})->throwsNoExceptions();
