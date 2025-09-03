<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;

#[ActionDescription(
    name: 'generate_manifest',
    description: 'Generates the manifest file for the application, summarizing its configuration and components.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class GenerateManifestAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {

        $this->logInvocation($this->describe()->name);
        $context->writeManifest();

        return $context;
    }
}
