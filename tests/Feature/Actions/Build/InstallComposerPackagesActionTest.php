<?php

use Glugox\Magic\Actions\Build\InstallComposerPackagesAction;
use Glugox\Magic\Support\BuildContext;

beforeEach(function () {
    // Create a dummy BuildContext
    $this->context = getFixtureBuildContext();
    // Create dummy composer.json in the base path
    $composerJsonPath = base_path('composer.json');
    file_put_contents($composerJsonPath, json_encode([
        'require' => new stdClass(),
        'require-dev' => new stdClass(),
    ]));
});

afterEach(function () {
    // Cleanup the dummy composer.json
    $composerJsonPath = base_path('composer.json');
    if (file_exists($composerJsonPath)) {
        unlink($composerJsonPath);
    }
});

it('runs composer installation for missing packages', function () {
    $action = $this->partialMock(InstallComposerPackagesAction::class, function ($mock) {
        // Override the runProcess method to not actually execute composer
        $mock->shouldAllowMockingProtectedMethods()
            ->makePartial()
            ->shouldReceive('runProcess')
            ->times(6) // Expecting all configured packages to be installed
            ->withArgs(function ($command, $message) {
                expect($command[1])->toBe('require'); // composer require
                // expect($command[2])->toBeIn(['pestphp/pest', 'pestphp/pest-plugin-browser', 'glugox/model-meta']);

                // context

                return true;
            });
        // Mock isComposerPackageInstalled to return false, forcing installation
        $mock->shouldReceive('isComposerPackageInstalled')
            ->andReturnFalse();
    });

    $config = $action($this->context);
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

/*it('installs composer packages and logs appropriately', function () {
    $context = getFixtureBuildContext();

    $action = new InstallComposerPackagesAction();
    $action($context);
})->throwsNoExceptions();*/
