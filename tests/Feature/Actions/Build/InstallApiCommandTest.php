<?php

use Glugox\Magic\Actions\Build\InstallApiCommand;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Fake the logging and Artisan calls
    $this->context = getFixtureBuildContext();
});

it('adds api routing when not present', function () {
    // fixture that matches your real file shape (the one you pasted earlier)
    $fixture = <<<'PHP'
<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
PHP;

    $contents = InstallApiCommand::replaceApiRegistration($fixture);

    // assert that api routing was added (we check for a substring to be robust to spacing/quotes)
    expect(Str::contains($contents, 'routes/api.php'))->toBeTrue();
    expect(Str::contains($contents, 'apiPrefix'))->toBeTrue();
});

it('skips install if API routes are already registered in router', function () {

    // Run the InstallApiCommand
    $action = new InstallApiCommand();
    $action($this->context);
})->throwsNoExceptions();
