<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Traits\AsDescribableAction;

#[ActionDescription(
    name: 'generate_app',
    description: 'Generates the application from specified json configuration file',
    parameters: ['options' => 'Array of options including config file path, starter, and overrides']
)]
class GenerateAppAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __invoke(array $options): BuildContext
    {
        // Step 1: Parse the configuration file
        /** @var Config $config */
        $config = app(ResolveAppConfigAction::class)($options);
        if (! $config->isValid()) {
            throw new \Exception('Invalid configuration provided.');
        }
        // Step 1: Initialize BuildContext with options and config
        $buildContext = BuildContext::fromOptions($options)->setConfig($config);

        // Step 2: Publish Files
        $buildContext = app(PublishFilesAction::class)($buildContext);

        // Step 3: Generate Migrations
        $buildContext = app(GenerateMigrationsAction::class)($buildContext);

        // Step 4: Generate Models
        $buildContext = app(GenerateModelsAction::class)($buildContext);

        // Step 5: Generate Seeders
        $buildContext = app(GenerateSeedersAction::class)($buildContext);

        // Step 6: Generate API Resources
        $buildContext = app(GenerateApiResourcesAction::class)($buildContext);

        // Step 7: Generate Controllers
        $buildContext = app(GenerateControllersAction::class)($buildContext);

        // Step 8: Run Install API Command
        $buildContext = app(InstallApiCommand::class)($buildContext);

        // Step 9: Install Node Packages
        $buildContext = app(InstallNodePackagesAction::class)($buildContext);

        // Step 10: Generate Vue Pages
        $buildContext = app(GenerateVuePagesAction::class)($buildContext);

        // Step 11: Update Vue Pages
        $buildContext = app(UpdateVuePagesAction::class)($buildContext);

        // Step 12: Update Database
        $buildContext = app(UpdateDbAction::class)($buildContext);

        // Step 13: Write Manifest
        return app(GenerateManifestAction::class)($buildContext);
    }
}
