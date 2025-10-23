<?php

namespace Glugox\Magic\Actions\Build;

use Artisan;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;
use RuntimeException;

#[ActionDescription(
    name: 'install_api',
    description: 'Installs the API by running the install:api Artisan command.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class InstallApiCommand implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        // Check if "api:" already exists in withRouting
        if (! self::isApiInstalled()) {
            // Run the install:api Artisan command
            Artisan::call('install:api',
                [
                    '--force' => true,
                    '--no-interaction' => true
                ]
            );
        } else {
            Log::channel('magic')->info('API routing already registered in bootstrap/app.php. Skipping install:api command.');
        }

        $this->registerApiRouting();
        $this->registerApiAuth();
        $this->setStatefulApi();

        Log::channel('magic')->info('Finished install:api command.');

        return $context;
    }

    public static function isApiInstalled(): bool
    {
        $file = base_path('bootstrap/app.php');

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Unable to read bootstrap/app.php when checking API installation state.');
        }

        // Capture the withRouting(...) block
        if (preg_match('/->withRouting\s*\((.*?)\)/s', $contents, $matches)) {
            $args = $matches[1];

            // Check if api: is already defined
            if (! str_contains($args, 'api:')) {
                return false;
            }
        }

        return true;
    }

    public static function replaceApiRegistration(string $contents): string
    {
        // Capture the withRouting(...) block
        if (preg_match('/->withRouting\s*\((.*?)\)/s', $contents, $matches)) {
            $args = $matches[1];

            // Check if api: is already defined
            if (! str_contains($args, 'api:')) {
                $newArgs = mb_rtrim($args, ", \n").",
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',";

                return str_replace($matches[0], "->withRouting(\n$newArgs\n)", $contents);
            }
            Log::channel('magic')->info('API routing already registered in '.base_path('bootstrap/app.php').', skipping...');

        } else {
            Log::channel('magic')->error('Could not locate withRouting() block in '.base_path('bootstrap/app.php'));
        }

        return $contents;
    }

    /**
     * Ensure that the API routing is registered in bootstrap/app.php
     */
    private function registerApiRouting(): void
    {
        Log::channel('magic')->info('Ensuring API routing is registered in bootstrap/app.php');
        $file = base_path('bootstrap/app.php');

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Unable to read bootstrap/app.php while registering API routing.');
        }

        $contents = self::replaceApiRegistration($contents);
        file_put_contents($file, $contents);
        Log::channel('magic')->info('Registered API routing in bootstrap/app.php');
    }

    /**
     * Adds
     *
     * import { useApi } from "@/composables/useApi";
     *
     * const {initCsrf} = useApi();
     * initCsrf().then(() => {
     * console.log("CSRF token initialized");
     * });
     *
     * In resources/js/app.js
     *
     * @return void
     */
    private function registerApiAuth(): void
    {
        $appJsPath = resource_path('js/app.ts');
        if (! file_exists($appJsPath)) {
            Log::channel('magic')->warning(
                "app.js file not found in resources/js directory. Please add the following snippet manually to initialize CSRF token for API requests:\n\n".
                "import { useApi } from \"@/composables/useApi\";\n\n".
                "const {initCsrf} = useApi();\n".
                "initCsrf().then(() => {\n".
                "    console.log(\"CSRF token initialized\");\n".
                '});'
            );

            return;
        }

        $appJsContent = file_get_contents($appJsPath);
        if ($appJsContent === false) {
            Log::channel('magic')->warning('Unable to read app.ts while registering API auth snippets.');

            return;
        }
        $importSnippet = 'import { useApi } from "@/composables/useApi";';
        $initSnippet = "\nconst {initCsrf} = useApi();\ninitCsrf().then(() => {\n    console.log(\"CSRF token initialized\");\n});\n";

        // Check if snippets already exist
        if (str_contains($appJsContent, 'useApi') && str_contains($appJsContent, 'initCsrf()')) {
            Log::channel('magic')->info('API auth snippets already exist in app.js');

            return;
        }

        // Insert import snippet at the top
        $newContent = $importSnippet."\n\n".$appJsContent;

        // Append init snippet at the end
        $newContent .= $initSnippet;

        file_put_contents($appJsPath, $newContent);
        Log::channel('magic')->info('Added API auth snippets to app.js');
    }

    /**
     * Adds:
     * $middleware->statefulApi();
     *
     * Right below:
     * $middleware->encryptCookies...
     */
    private function setStatefulApi(): void
    {
        $file = base_path('bootstrap/app.php');

        $contents = file_get_contents($file);
        if ($contents === false) {
            Log::channel('magic')->warning('Unable to read bootstrap/app.php while setting stateful API.');

            return;
        }

        $snippet = '$middleware->statefulApi();';

        // Check if snippet already exists
        if (str_contains($contents, 'statefulApi()')) {
            Log::channel('magic')->info('statefulApi() snippet already exists in HandleInertiaRequests.php');

            return;
        }

        // Insert snippet right below $middleware->encryptCookies...
        $newContent = preg_replace(
            '/(\$middleware->encryptCookies\(.*?\);\s*)/s',
            "$1\n        ".$snippet."\n",
            $contents,
            1
        );

        if ($newContent === null) {
            Log::channel('magic')->warning('Failed to update bootstrap/app.php with statefulApi snippet.');

            return;
        }

        file_put_contents($file, $newContent);
        Log::channel('magic')->info('Added statefulApi() snippet to HandleInertiaRequests.php');
    }
}
