<?php

namespace Glugox\Magic\Actions\Build;

use Exception;
use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Traits\AsDescribableAction;
use ReflectionException;

#[ActionDescription(
    name: 'generate_app',
    description: 'Generates the application from specified json configuration file',
    parameters: ['options' => 'Array of options including config file path, starter, and overrides']
)]
class GenerateAppAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param  array{ config?: string, starter?: string, set?: array<string, mixed> }  $options
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function __invoke(array $options): BuildContext
    {
        // Step 1: Parse the configuration file
        /** @var Config $config */
        $config = app(ResolveAppConfigAction::class)($options);
        if (! $config->isValid()) {
            throw new Exception('Invalid configuration provided.');
        }
        // Step 1: Initialize BuildContext with options and config
        $buildContext = BuildContext::fromOptions($options)->setConfig($config);

        // Step 2: Publish Files
        $buildContext = app(PublishFilesAction::class)($buildContext);

        // Step 3: Install Node Packages
        $buildContext = app(InstallNodePackagesAction::class)($buildContext);

        // Step 4: Install Composer Packages
        $buildContext = app(InstallComposerPackagesAction::class)($buildContext);

        // Step 5: Generate Enums
        $buildContext = app(GenerateEnumsAction::class)($buildContext);

        // Step 6: Run Install API Command
        $buildContext = app(InstallApiCommand::class)($buildContext);

        // Step 7: Enable Attachable (copy traits, resources, stubs conditionally)
        $buildContext = app(EnableAttachableAction::class)($buildContext);

        // Step 8: Generate Migrations
        $buildContext = app(GenerateMigrationsAction::class)($buildContext);

        // Step 9: Generate Models
        $buildContext = app(GenerateModelsAction::class)($buildContext);

        // Step 9.1: Generate ModelMeta classes
        $buildContext = app(GenerateModelMetaAction::class)($buildContext);

        // Step 10: Generate Seeders
        $buildContext = app(GenerateSeedersAction::class)($buildContext);

        // Step 11: Generate API Resources
        $buildContext = app(GenerateApiResourcesAction::class)($buildContext);

        // Step 12: Generate Controllers
        $buildContext = app(GenerateControllersAction::class)($buildContext);

        // Step 13: Generate Vue Pages
        $buildContext = app(GenerateVuePagesAction::class)($buildContext);

        // Step 14: Update Vue Pages
        $buildContext = app(UpdateVuePagesAction::class)($buildContext);

        // Step 15: Update Database
        $buildContext = app(UpdateDbAction::class)($buildContext);

        // Step 16: Write Manifest
        $buildContext = app(GenerateManifestAction::class)($buildContext);

        // Step 17: Setup Development Environment (optional)
        $buildContext = app(SetupDevelopmentEnvAction::class)($buildContext);

        // Step 18: Generate CRUD tests for models
        $buildContext = app(GenerateCrudTestsAction::class)($buildContext);

        // Return the final BuildContext
        return $buildContext;
    }
}
