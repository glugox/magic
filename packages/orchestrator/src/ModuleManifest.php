<?php

namespace Glugox\Orchestrator;

use RuntimeException;

class ModuleManifest
{
    public function __construct(protected string $path)
    {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function load(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $data = include $this->path;

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $modules
     */
    public function write(array $modules): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory [%s] for module manifest.', $directory));
        }

        $export = var_export($modules, true);
        $content = <<<PHP
<?php

return {$export};
PHP;

        file_put_contents($this->path, $content);
    }

    public function delete(): void
    {
        if ($this->exists()) {
            unlink($this->path);
        }
    }
}
