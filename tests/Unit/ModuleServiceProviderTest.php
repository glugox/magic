<?php

use Glugox\Module\ModuleServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

it('boots module resources when directories exist', function (): void {
    $this->app->register(FixtureModuleServiceProvider::class);
    $this->app->boot();

    expect(config('module-fixture.enabled'))->toBeTrue();
    expect(config('module-fixture.nested.value'))->toBe('from-config');

    $response = app('router')->dispatch(Request::create('/module-fixture', 'GET'));
    expect($response->getContent())->toBe('module-fixture-route');
    expect(Route::has('module.fixture.index'))->toBeTrue();

    expect(View::exists('fixture::example'))->toBeTrue();
    expect(View::make('fixture::example')->render())->toContain('Module Fixture View');

    expect(__('module-fixture::messages.greeting'))->toBe('Hello from module fixture');
    expect(__('Module Fixture JSON'))->toBe('Module fixture json translation');

    $commands = array_keys(Artisan::all());
    expect($commands)->toContain('module-fixture:test');

    $migrator = $this->app->make('migrator');
    expect($migrator->paths())->toContain(module_fixture_path('database/migrations'));
});

class FixtureModuleCommand extends Command
{
    protected $signature = 'module-fixture:test';

    protected $description = 'Test command for the module fixture.';

    public function handle(): int
    {
        $this->info('Module fixture command executed.');

        return self::SUCCESS;
    }
}

class FixtureModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->registerModule();
    }

    public function boot(): void
    {
        $this->bootModule();
    }

    protected function moduleBasePath(): string
    {
        return module_fixture_path();
    }

    protected function moduleViewNamespace(): string
    {
        return 'fixture';
    }

    protected function moduleCommands(): array
    {
        return [FixtureModuleCommand::class];
    }
}

function module_fixture_path(string $path = ''): string
{
    $base = realpath(__DIR__.'/../data/module-fixture');

    if ($base === false) {
        $base = __DIR__.'/../data/module-fixture';
    }

    if ($path === '') {
        return $base;
    }

    return $base.DIRECTORY_SEPARATOR.Str::replace('/', DIRECTORY_SEPARATOR, $path);
}
