<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;

#[ActionDescription(
    name: 'generate_manifest',
    description: 'Generates the manifest file for the application, summarizing its configuration and components.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateManifestAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(BuildContext $context): BuildContext
    {

        $context->writeManifest();

        return $context;
    }
}
