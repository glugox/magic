<?php

use Glugox\Orchestrator\ModuleManager;
use Glugox\Orchestrator\SpecDescriptor;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use RuntimeException;

uses(Tests\TestCase::class);

afterEach(function (): void {
    Mockery::close();
});

it('delegates to magic:build with namespace derived path', function () {
    $spec = new SpecDescriptor(
        'vendor/foo-module',
        'Foo Module',
        'Vendor\\Foo',
        '/tmp/specs/vendor/foo-module.json'
    );

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('reload')->once();
    $manager->shouldReceive('specs')->andReturn(collect([$spec]));
    $manager->shouldReceive('modulesPath')->andReturn('/sandbox/modules');

    $this->app->instance(ModuleManager::class, $manager);

    $captured = [];

    Artisan::command('magic:build', function () use (&$captured) {
        $captured = [
            'package-path' => $this->option('package-path'),
            'package-name' => $this->option('package-name'),
            'package-namespace' => $this->option('package-namespace'),
            'config' => $this->option('config'),
        ];

        return 0;
    });

    $this->artisan('orchestrator:build')
        ->expectsOutput('Building module [vendor/foo-module]...')
        ->expectsOutput('Module [vendor/foo-module] built.')
        ->assertExitCode(0);

    expect($captured)->toMatchArray([
        'package-path' => '/sandbox/modules/Vendor/Foo',
        'package-name' => 'vendor/foo-module',
        'package-namespace' => 'Vendor\\Foo',
        'config' => '/tmp/specs/vendor/foo-module.json',
    ]);
});

it('fails when no matching spec exists', function () {
    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('reload')->once();
    $manager->shouldReceive('specs')->andReturn(collect());

    $this->app->instance(ModuleManager::class, $manager);

    $this->artisan('orchestrator:build', ['module' => 'missing/spec'])
        ->expectsOutput('No module spec registered for [missing/spec].')
        ->assertExitCode(1);
});

it('logs helpful context when magic build throws an exception', function () {
    $specDirectory = base_path('.tmp/specs');

    if (! is_dir($specDirectory)) {
        mkdir($specDirectory, 0777, true);
    }

    $specPath = $specDirectory.'/vendor-foo-module.json';

    file_put_contents($specPath, json_encode([
        'app' => [
            'name' => 'Foo Module',
            'vendor' => 'Vendor',
            'version' => '1.0.0',
        ],
    ], JSON_THROW_ON_ERROR));

    $spec = new SpecDescriptor(
        'vendor/foo-module',
        'Foo Module',
        'Vendor\\Foo',
        $specPath
    );

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('reload')->once();
    $manager->shouldReceive('specs')->andReturn(collect([$spec]));
    $manager->shouldReceive('modulesPath')->andReturn('/sandbox/modules');

    $this->app->instance(ModuleManager::class, $manager);

    Artisan::command('magic:build', function () {
        throw new RuntimeException('boom');
    });

    $this->artisan('orchestrator:build')
        ->expectsOutput('Building module [vendor/foo-module]...')
        ->expectsOutputToContain('Failed to build module [vendor/foo-module]: boom')
        ->expectsOutputToContain('Spec file: '.$specPath)
        ->expectsOutputToContain('Spec sections: app')
        ->expectsOutputToContain('Spec app summary: {"name":"Foo Module","vendor":"Vendor"}')
        ->expectsOutputToContain('Exception trace:')
        ->assertExitCode(1);

    @unlink($specPath);
});
