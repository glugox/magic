<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Build\Migration\GenerateMigrationForEntityAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'generate_migrations',
    description: 'Generates a migration files for all entities found in Config into /database/migrations',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateMigrationsAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        // Stubs path
        $stubsPath = __DIR__.'/../../../stubs';

        $config = $context->getConfig();

        // For every entity in config, generate migration
        foreach ($config->entities as $entity) {
            // Action call -- Use GenerateMigrationForEntityAction to generate migration for each entity
            /** @var FilesGenerationUpdate $filesGenerationUpdate */
            $filesGenerationUpdate = app(GenerateMigrationForEntityAction::class)($entity);

            // Register what is created/updated/deleted
            $context->mergeFilesGenerationUpdate($filesGenerationUpdate);
        }

        return $context;
    }
}
