<?php

declare(strict_types=1);

namespace Glugox\Builder\Commands;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\MagicPaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
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

    protected $description = 'Generate a lightweight Magic package module using package mode.';

    public function handle(): int
    {
        $configPath = (string) $this->option('config');
        $packagePath = (string) $this->option('package-path');

        if ($configPath === '') {
            $this->error('The --config option is required.');

            return CommandAlias::FAILURE;
        }

        if ($packagePath === '') {
            $this->error('The --package-path option is required.');

            return CommandAlias::FAILURE;
        }

        try {
            $config = Config::fromJsonFile($configPath);
        } catch (JsonException|RuntimeException|Throwable $exception) {
            $this->error('Failed to load configuration: '.$exception->getMessage());

            return CommandAlias::FAILURE;
        }

        $entity = $config->entities[0] ?? null;

        if (! $entity instanceof Entity) {
            $this->error('The configuration must define at least one entity.');

            return CommandAlias::FAILURE;
        }

        MagicPaths::usePackage($packagePath);

        try {
            $this->generateApiRoute($entity);
        } finally {
            MagicPaths::clearPackage();
        }

        $this->info(sprintf('Generated API route for the %s entity.', $entity->getName()));

        return CommandAlias::SUCCESS;
    }

    private function generateApiRoute(Entity $entity): void
    {
        $routePath = MagicPaths::routes('api.php');
        File::ensureDirectoryExists(dirname($routePath));

        $fields = array_map(
            static fn (Field $field): string => $field->name,
            array_values(array_filter($entity->fields ?? [], static fn ($field): bool => $field instanceof Field))
        );

        $fieldsBlock = $this->formatFieldArray($fields);
        $routeSlug = Str::kebab(Str::pluralStudly($entity->getName()));

        $content = <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function (): void {
    Route::get('{$routeSlug}', static function () {
        return [
            'entity' => '{$entity->getName()}',
            'fields' => {$fieldsBlock},
        ];
    });
});

PHP;

        File::put($routePath, $content);
    }

    /**
     * @param  string[]  $fields
     */
    private function formatFieldArray(array $fields): string
    {
        if ($fields === []) {
            return '[]';
        }

        $lines = array_map(
            static fn (string $field): string => "'".str_replace("'", "\\'", $field)."',",
            $fields
        );

        $indented = implode("\n            ", $lines);

        return "[\n            {$indented}\n        ]";
    }
}
