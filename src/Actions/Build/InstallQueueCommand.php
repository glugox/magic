<?php

namespace Glugox\Magic\Actions\Build;

use Artisan;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'install_queue',
    description: 'Installs the queue system by running the make:queue-table Artisan command.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class InstallQueueCommand implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        if (MagicPaths::isUsingPackage()) {
            Log::channel('magic')->info('Skipping queue installation in package generation mode.');

            return $context;
        }

        // Run the install:api Artisan command
        Artisan::call('make:queue-table');

        Log::channel('magic')->info('Finished make:queue-table command.');

        return $context;
    }
}
