<?php

namespace Glugox\Magic\Actions\Build;

use Artisan;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'install_api',
    description: 'Installs the API by running the install:api Artisan command.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class InstallApiCommand implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        // Check if api already installed
        $file = __DIR__.'/../../../stubs/laravel/bootstrap/app.php';
        $contents = file_get_contents($file);

        // Check if "api:" already exists in withRouting
        if (preg_match('/->withRouting\s*\([^)]*api:/m', $contents)) {
            Log::channel('magic')->info('API routing already registered in bootstrap/app.php. Skipping install:api command.');

            return $context;
        }

        // Run the install:api Artisan command
        Artisan::call('install:api',
            [
                '--force' => true,
                '--no-interaction' => true
            ]
        );

        Log::channel('magic')->info('Finished install:api command.');

        return $context;
    }

    /*private function registerApiRouting(): void
    {
        Log::channel('magic')->info('Ensuring API routing is registered in bootstrap/app.php');
        $file = base_path('bootstrap/app.php');
        $contents = file_get_contents($file);

        // Safer check: does "api:" already exist in withRouting?
        if (! preg_match('/->withRouting\s*\([^)]*api:/m', $contents)) {
            $contents = preg_replace(
                '/->withRouting\s*\(([^)]*)\)/s',
                '->withRouting($1
            api: __DIR__.\'/../routes/api.php\',
            apiPrefix: \'api\')',
                $contents,
                1
            );

            file_put_contents($file, $contents);
            Log::channel('magic')->info('Registered API routing in bootstrap/app.php');
        } else {
            Log::channel('magic')->info('API routing already registered, skipping...');
        }
    }*/
}
