<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[ActionDescription(
    name: 'install_node_packages',
    description: 'Ensures that specified shadcn-vue components and npm packages are installed in the project.',
    parameters: ['context' => 'The BuildContext instance.']
)]
class InstallNodePackagesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected array $npmPackages = [
        'axios',
        // 'dayjs',
        // add more npm packages here...
    ];

    protected array $shadcnComponents = [

        'calendar',
        'combobox',
        'command',
        'dropdown-menu',
        'form',
        'pagination',
        'popover',
        'select',
        'switch',
        'table',
        'textarea',
        // add more components here...
    ];

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        Log::channel('magic')->info('Checking Node.js dependencies...');

        $packageJsonPath = base_path('package.json');
        $packageJson = file_exists($packageJsonPath)
            ? json_decode(file_get_contents($packageJsonPath), true)
            : [];

        // 1. Install missing npm packages in one command
        $missingPackages = array_filter($this->npmPackages, fn ($pkg) => ! $this->isPackageInstalled($pkg, $packageJson));

        if (! empty($missingPackages)) {
            Log::channel('magic')->info('Installing missing npm packages: '.implode(', ', $missingPackages));

            $this->runProcess(
                array_merge(['/usr/local/bin/npm', 'install', '--save-dev'], $missingPackages),
                'Installing npm packages...'
            );
        } else {
            Log::channel('magic')->info('All npm packages are already installed.');
        }

        // 2. Install missing shadcn-vue components in one command
        $missingComponents = array_filter($this->shadcnComponents, fn ($component) => ! $this->isShadcnInstalled($component));

        if (! empty($missingComponents)) {
            Log::channel('magic')->info('Installing missing shadcn-vue components: '.implode(', ', $missingComponents));

            foreach ($missingComponents as $component) {

                if ($this->isShadcnInstalled($component)) {
                    Log::channel('magic')->info("Component {$component} is already installed, skipping.");

                    continue;
                }

                $this->runProcess(
                    ['/usr/local/bin/npx', 'shadcn-vue@latest', 'add', $component],
                    "Installing shadcn-vue component: {$component}..."
                );
            }
        } else {
            Log::channel('magic')->info('All shadcn-vue components are already installed.');
        }

        Log::channel('magic')->info('All Node.js dependencies are up to date!');

        return $context;
    }

    /**
     * Check if a shadcn-vue component is installed by looking for its file or directory.
     * A component can be a single .vue file or a directory with multiple files.
     * E.g., for 'table', it checks for resources/js/components/ui/table.vue
     *
     * TODO: Do we need to check for folder by component name as well? Eg. for 'date-picker', check for resources/js/components/ui/date-picker/ ?
     */
    public function isShadcnInstalled(string $component): bool
    {
        $uiPath = resource_path("js/components/ui/{$component}.vue");

        return file_exists($uiPath) || is_dir(resource_path("js/components/ui/{$component}"));
    }

    /**
     * Check if a package is installed by looking into package.json
     * dependencies and devDependencies.
     */
    public function isPackageInstalled(string $package, array $packageJson): bool
    {
        return isset($packageJson['dependencies'][$package]) || isset($packageJson['devDependencies'][$package]);
    }

    /**
     * Run a Symfony Process command and handle output and errors.
     */
    public function runProcess(array $command, string $message): void
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
