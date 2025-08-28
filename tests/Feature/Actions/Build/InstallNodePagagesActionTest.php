<?php

use Glugox\Magic\Actions\Build\InstallNodePackagesAction;
use Glugox\Magic\Support\BuildContext;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;

beforeEach(function () {
    $this->context = Mockery::mock(BuildContext::class);

    // Mock logging globally
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->andReturnNull();
});

it('skips already installed shadcn components', function () {
    $action = Mockery::mock(InstallNodePackagesAction::class)->makePartial();

    // Make shadcn components appear installed
    $action->shouldReceive('isShadcnInstalled')->andReturn(true);

    // runProcess should be called once just to install npm packages, not shadcn components
    $action->shouldReceive('runProcess')->once();

    ($action)($this->context);
});

it('runs process for missing shadcn components without executing real command', function () {
    $action = Mockery::mock(InstallNodePackagesAction::class)->makePartial();

    // Components are missing
    $action->shouldReceive('isShadcnInstalled')->andReturn(false);
    $action->shouldReceive('isPackageInstalled')->andReturn(true);

    // Mock runProcess to prevent actual command execution
    $action->shouldReceive('runProcess')->once()->withArgs(function ($command, $message) {
        expect($command[0])->toBe('/usr/local/bin/npx');
        expect($command[1])->toContain('shadcn-vue@latest');
        return true;
    });

    ($action)($this->context);
});

it('skips already installed npm packages', function () {
    $action = Mockery::mock(InstallNodePackagesAction::class)->makePartial();

    // Make npm packages appear installed
    $action->shouldReceive('isPackageInstalled')->andReturn(true);

    // runProcess should be called once just to install shadcn components, not npm packages
    $action->shouldReceive('runProcess')->once();

    ($action)($this->context);
});

it('runs process for missing npm packages without executing real command', function () {
    $action = Mockery::mock(InstallNodePackagesAction::class)->makePartial();

    // Packages are missing
    $action->shouldReceive('isPackageInstalled')->andReturn(false);
    $action->shouldReceive('isShadcnInstalled')->andReturn(true);

    // Mock runProcess to prevent actual command execution
    $action->shouldReceive('runProcess')->once()->withArgs(function ($command, $message) {
        expect($command[0])->toBe('/usr/local/bin/npx');
        expect($command[1])->toBe('install');
        expect($command[2])->toBe('--save-dev');
        return true;
    });

    ($action)($this->context);

});
