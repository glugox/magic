<?php

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\ModuleManager;
use Glugox\Orchestrator\SpecDescriptor;
use InvalidArgumentException;
use Mockery;
use Tests\Fixtures\FakeModuleServiceProvider;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config()->set('orchestrator.dev_tools.prefix', 'dev/orchestrator');
    config()->set('orchestrator.dev_tools.middleware', []);
    config()->set('orchestrator.dev_tools.enabled', false);
    config()->set('app.debug', false);
});

afterEach(function (): void {
    Mockery::close();
});

it('does not expose dev routes when disabled', function () {
    $this->get('/dev/orchestrator')->assertNotFound();
});

it('auto enables dev routes when app debug mode is on', function () {
    config()->set('orchestrator.dev_tools.enabled', null);
    config()->set('app.debug', true);

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('all')->andReturn(collect());
    $manager->shouldReceive('installed')->andReturn(collect());
    $manager->shouldReceive('enabledModules')->andReturn(collect());
    $manager->shouldReceive('specs')->andReturn(collect());

    $this->app->instance(ModuleManager::class, $manager);

    $this->get('/dev/orchestrator')->assertOk();
});

it('exposes orchestrator diagnostics when enabled', function () {
    config()->set('orchestrator.dev_tools.enabled', true);

    $module = ModuleDescriptor::fromArray([
        'id' => 'glugox/crm',
        'name' => 'Glugox CRM',
        'version' => '1.2.3',
        'installed' => true,
        'enabled' => true,
        'base_path' => __DIR__,
        'paths' => [],
        'providers' => [FakeModuleServiceProvider::class],
        'capabilities' => [],
        'extra' => [],
    ]);

    $spec = new SpecDescriptor('glugox/crm', 'Glugox CRM', 'Glugox\\Crm', __DIR__.'/../Fixtures/specs/crm.json');

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('all')->andReturn(collect([$module]));
    $manager->shouldReceive('installed')->andReturn(collect([$module]));
    $manager->shouldReceive('enabledModules')->andReturn(collect([$module]));
    $manager->shouldReceive('specs')->andReturn(collect([$spec]));

    $this->app->instance(ModuleManager::class, $manager);

    $response = $this->get('/dev/orchestrator');

    $response
        ->assertOk()
        ->assertJson([
            'summary' => [
                'total_modules' => 1,
                'installed_modules' => 1,
                'enabled_modules' => 1,
                'total_specs' => 1,
            ],
            'modules' => [
                [
                    'id' => 'glugox/crm',
                    'health' => [
                        'status' => 'healthy',
                        'healthy' => true,
                        'base_path_exists' => true,
                    ],
                ],
            ],
            'specs' => [
                [
                    'id' => 'glugox/crm',
                    'config_path' => __DIR__.'/../Fixtures/specs/crm.json',
                ],
            ],
        ])
        ->assertJsonFragment([
            'class' => FakeModuleServiceProvider::class,
            'exists' => true,
            'loaded' => true,
        ]);

    $expectedProviderPath = realpath(__DIR__.'/../Fixtures/FakeModuleServiceProvider.php');
    $this->assertIsString($expectedProviderPath);

    $response->assertJsonPath(
        'modules.0.provider_diagnostics.0.path',
        $expectedProviderPath
    );
});

it('returns module diagnostics for a specific module', function () {
    config()->set('orchestrator.dev_tools.enabled', true);

    $module = ModuleDescriptor::fromArray([
        'id' => 'glugox/crm',
        'name' => 'Glugox CRM',
        'version' => '1.2.3',
        'installed' => true,
        'enabled' => true,
        'base_path' => __DIR__,
        'paths' => [],
        'providers' => ['Missing\\Provider'],
        'capabilities' => [],
        'extra' => [],
    ]);

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('module')->with('glugox/crm')->andReturn($module);

    $this->app->instance(ModuleManager::class, $manager);

    $this->get('/dev/orchestrator/modules/glugox%2Fcrm')
        ->assertOk()
        ->assertJson([
            'id' => 'glugox/crm',
            'health' => [
                'status' => 'healthy',
                'healthy' => true,
            ],
        ])
        ->assertJsonFragment([
            'class' => 'Missing\\Provider',
            'exists' => false,
            'loaded' => false,
            'path' => null,
        ]);
});

it('returns a 404 when requesting an unknown module', function () {
    config()->set('orchestrator.dev_tools.enabled', true);

    $manager = Mockery::mock(ModuleManager::class);
    $manager->shouldReceive('module')
        ->with('missing/module')
        ->andThrow(new InvalidArgumentException('Module [missing/module] is not registered in the orchestrator manifest.'));

    $this->app->instance(ModuleManager::class, $manager);

    $this->get('/dev/orchestrator/modules/missing%2Fmodule')
        ->assertNotFound()
        ->assertJson([
            'message' => 'Module [missing/module] is not registered in the orchestrator manifest.',
        ]);
});
