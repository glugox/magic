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
    name: 'update_db',
    description: 'Updates the database by running migrations and optionally seeding it with default data based on the configuration settings.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class UpdateDbAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        // Step 1. Run migrations to ensure the database schema is up to date
        Log::channel('magic')->info('Running database migrations to update the schema...');
        Artisan::call('migrate', ['--force' => true]);

        // Step 2. Seed the database if enabled in config
        //if ($context->getConfig()->app->seedEnabled) {
            Log::channel('magic')->info("Seeding the database with default seedCount of {$context->getConfig()->app->seedCount}...");

            // Run the db:seed Artisan command with the --force option
            Artisan::call('db:seed', ['--force' => true]);

        //} else {
        //    Log::channel('magic')->debug('Database seeding is disabled in the config.');
        //}

        return $context;
    }
}
