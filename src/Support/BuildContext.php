<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Actions\Config\ResolveAppConfigAction;
use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\File\FilesGenerationUpdate;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionException;
use RuntimeException;

class BuildContext
{
    /**
     * Errors encountered during the build process.
     */
    public array $errors = [];

    /**
     * Config object resolved from the config file, starter, and overrides.
     */
    protected ?Config $config = null;

    /**
     * Keeps track of files generated, updated, or deleted during the build.
     */
    protected FilesGenerationUpdate $filesGenerationUpdate;

    /**
     * Constructor.
     */
    public function __construct(
        public ?string $configPath = null,
        public ?string $starter = null,
        public array $overrides = []
    ) {
        $this->filesGenerationUpdate = new FilesGenerationUpdate;
    }

    /**
     * Create a BuildContext from options array.
     */
    public static function fromOptions(array $options): self
    {
        $overrides = $options['overrides'] ?? $options['set'] ?? [];

        if (is_string($overrides)) {
            $overrides = [$overrides];
        }

        return new self(
            configPath: $options['config'] ?? null,
            starter: $options['starter'] ?? null,
            overrides: is_array($overrides) ? $overrides : []
        );
    }

    /**
     * Returns a new instance with the provided config.
     */
    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Return Config object.
     */
    public function getConfig(): Config
    {
        $this->ensureConfigLoaded();

        return $this->config ?? throw new RuntimeException('Config could not be loaded.');
    }

    /**
     * Registers a file that is generated or copied as part of the build process.
     */
    public function registerGeneratedFile(string|array $filePath): void
    {
        // Ensure we have an array of file paths
        $filePaths = is_array($filePath) ? $filePath : [$filePath];
        Log::channel('magic')->info('Generated file(s): '.implode(', ', $filePaths));

        foreach ($filePaths as $path) {
            $this->filesGenerationUpdate->addCreated($path);
        }
    }

    /**
     * Registers created folders as part of the build process.
     */
    public function registerCreatedFolder(string $folderPath): void
    {
        Log::channel('magic')->info("Created folder: {$folderPath}");
        $this->filesGenerationUpdate->addFolder($folderPath);
    }

    /**
     * Merges another FilesGenerationUpdate into this one
     */
    public function mergeFilesGenerationUpdate(FilesGenerationUpdate $other): self
    {
        $this->filesGenerationUpdate->merge($other);

        return $this;
    }

    /**
     * Returns registry of what happened during the build process.
     */
    public function getFilesGenerationUpdate()
    {
        return $this->filesGenerationUpdate;
    }

    /**
     * Returns true if the build has errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Returns errors as a formatted string.
     */
    public function error(): string
    {
        return implode("\n", $this->errors);
    }

    public function registerUpdatedFile(string $webPhpPath)
    {
        $this->filesGenerationUpdate->addUpdated($webPhpPath);
        Log::channel('magic')->info("Updated file: {$webPhpPath}");
    }

    public function writeManifest()
    {
        $this->filesGenerationUpdate->writeManifest();
    }

    /**
     * Ensure the config is loaded.
     */
    private function ensureConfigLoaded(): void
    {
        if ($this->config === null) {
            try {
                $this->config = app(ResolveAppConfigAction::class)([
                    'config' => $this->configPath,
                    'starter' => $this->starter,
                    'overrides' => $this->overrides,
                ]);
            } catch (JsonException|ReflectionException $e) {
                Log::channel('magic')->critical("Failed to load config file: {$e->getMessage()}");
            }
        }
    }
}
