<?php

namespace Glugox\Magic\Actions\Build;

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

        // Eneble slow server for development purposes
        // $this->enableSlowServer($context);

        // Enable Pest RefreshDatabase trait for browser tests
        $this->enablePestRefreshDatabase();

        return $context;
    }

    /**
     * Adds
     *
     * pest()->extend(Tests\TestCase::class)
     * ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
     * ->in('Browser');
     *
     * In tests/Pest.php
     */
    private function enablePestRefreshDatabase(): void
    {
        $pestFilePath = base_path('tests/Pest.php');
        if (! file_exists($pestFilePath)) {
            Log::channel('magic')->warning(
                "Pest.php file not found in tests directory. Please add the following snippet manually to enable RefreshDatabase trait for browser tests:\n\n".
                "pest()->extend(Tests\\TestCase::class)\n->use(Illuminate\\Foundation\\Testing\\RefreshDatabase::class)\n->in('Browser');"
            );

            return;
        }

        $pestFileContent = file_get_contents($pestFilePath);
        $snippet = <<<'EOT'
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Browser');
EOT;

        // Check if snippet already exists
        if (str_contains($pestFileContent, '->in(\'Browser\')')) {
            Log::channel('magic')->info('RefreshDatabase trait snippet already exists in Pest.php');

            return;
        }

        // Insert snippet immediately after <?php
        $newContent = preg_replace(
            '/^<\?php\s*/',
            "<?php\n\n".$snippet."\n\n",
            $pestFileContent,
            1
        );

        file_put_contents($pestFilePath, $newContent);
        Log::channel('magic')->info('Added RefreshDatabase trait snippet to Pest.php');
    }

    /**
     * Adds a snippet to main.js or app.js to simulate a slow server in development environment.
     */
    private function enableSlowServer(BuildContext $context): void
    {
        /**
         * Write this in main.js or app.js to simulate slow server in development en
         */
        $slowServerSnippet = <<<'EOT'
import axios from "axios";
if (import.meta.env.DEV) {
    // Add a global delay to simulate slow server
    axios.interceptors.request.use(async (config) => {
        await new Promise((resolve) => setTimeout(resolve, 600)) // delay
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

            return;
        }

        // Check if the snippet already exists to avoid duplication
        $mainFileContent = file_get_contents($mainFilePath);
        if (! str_contains($mainFileContent, 'import.meta.env.DEV')) {
            file_put_contents($mainFilePath, $mainFileContent."\n".$slowServerSnippet);
            Log::channel('magic')->info("Added slow server simulation snippet to $mainFilePath");
        } else {
            Log::channel('magic')->info("Slow server simulation snippet already exists in $mainFilePath");
        }
    }
}
