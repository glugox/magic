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
    name: 'setup_development_env',
    description: 'Sets up the development environment with configurations and tools to enhance the developer experience.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class SetupDevelopmentEnvAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        /**
         * Write this in main.js or app.js to simulate slow server in development en
         */
        $slowServerSnippet = <<<EOT
import axios from "axios";
if (import.meta.env.DEV) {
    // Add a global delay to simulate slow server
    axios.interceptors.request.use(async (config) => {
        await new Promise((resolve) => setTimeout(resolve, 1500)) // 1.5s delay
        return config
    })
}
EOT;
        $mainJsPath = resource_path('js/main.ts');
        $mainAppPath = resource_path('js/app.ts');
        if (file_exists($mainJsPath)) {
            $mainFilePath = $mainJsPath;
        } elseif (file_exists($mainAppPath)) {
            $mainFilePath = $mainAppPath;
        } else {
            Log::channel('magic')->warning("Neither main.ts nor app.ts found in resources/js. Please add the following snippet manually to simulate a slow server in development environment:\n$slowServerSnippet");
            return $context;
        }

        // Check if the snippet already exists to avoid duplication
        $mainFileContent = file_get_contents($mainFilePath);
        if (!str_contains($mainFileContent, 'import.meta.env.DEV')) {
            file_put_contents($mainFilePath, $mainFileContent . "\n" . $slowServerSnippet );
            Log::channel('magic')->info("Added slow server simulation snippet to $mainFilePath");
        } else {
            Log::channel('magic')->info("Slow server simulation snippet already exists in $mainFilePath");
        }

        return $context;
    }
}
