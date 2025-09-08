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

        // Check if API is already installed by checking routes/api.php
        $apiRoutesPath = base_path('routes/api.php');
        if (file_exists($apiRoutesPath) && filesize($apiRoutesPath) > 0) {
            Log::channel('magic')->info('API routes already exist. Skipping install:api command.');

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
}
