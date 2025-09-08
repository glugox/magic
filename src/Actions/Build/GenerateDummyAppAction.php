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
    name: 'generate_dummy_app',
    description: 'Generates dummy application for testing purposes',
    parameters: []
)]
class GenerateDummyAppAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function __invoke(): void
    {
        $options = [
            'starter' => 'inventory'
        ];

        /** @var Config $config */
        $config = app(ResolveAppConfigAction::class)($options);
        if (! $config->isValid()) {
            throw new Exception('Invalid configuration provided.');
        }
        // Step 1: Initialize BuildContext with options and config
        $buildContext = BuildContext::fromOptions($options)->setConfig($config);

        // Action calls to generate the app
        app(GenerateAppAction::class)($options);
    }
}
