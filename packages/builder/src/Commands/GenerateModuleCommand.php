<?php

declare(strict_types=1);

namespace Glugox\Builder\Commands;

use Glugox\Builder\Actions\GenerateApiRouteAction;
use Glugox\Builder\Actions\LoadConfigAction;
use Glugox\Builder\Actions\ResolvePrimaryEntityAction;
use Glugox\Builder\Actions\WriteFileAction;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

class GenerateModuleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'builder:generate
        {--config= : Path to the JSON configuration file}
        {--package-path= : Directory where the package should be generated}';

    protected $description = 'Generate a lightweight package-mode API route based on a Magic config.';

    public function __construct(
        private readonly LoadConfigAction $loadConfig,
        private readonly ResolvePrimaryEntityAction $resolvePrimaryEntity,
        private readonly GenerateApiRouteAction $generateApiRoute,
        private readonly WriteFileAction $writeFile,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $configPath = (string) $this->option('config');
        $packagePath = (string) $this->option('package-path');

        if ($packagePath === '') {
            $this->error('The --package-path option is required.');

            return CommandAlias::FAILURE;
        }

        try {
            $config = ($this->loadConfig)($configPath);
            $entity = ($this->resolvePrimaryEntity)($config);
            $routeContents = ($this->generateApiRoute)($entity);

            $routePath = $this->resolveRoutePath($packagePath);

            ($this->writeFile)($routePath, $routeContents);

            $this->info(sprintf('Generated API route for the %s entity.', $entity->name));

            return CommandAlias::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return CommandAlias::FAILURE;
        } catch (Throwable $exception) {
            $this->error('An unexpected error occurred: '.$exception->getMessage());

            return CommandAlias::FAILURE;
        }
    }

    private function resolveRoutePath(string $packagePath): string
    {
        $normalized = rtrim($packagePath, DIRECTORY_SEPARATOR);

        return $normalized.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'api.php';
    }
}
