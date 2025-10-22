<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Actions\Files\BackupOriginalFileAction;

class ComposerHelper
{
    protected string $path;
    protected array $composer;

    public function __construct(string $composerJsonPath)
    {
        $this->path = $composerJsonPath;

        if (!file_exists($this->path)) {
            throw new \RuntimeException("composer.json not found at {$this->path}");
        }

        $json = file_get_contents($this->path);
        $this->composer = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid composer.json: " . json_last_error_msg());
        }
    }

    /**
     * Ensure a local repository is added with type=path and symlink=true.
     */
    public function ensureLocalRepo(string $url): void
    {
        if (!isset($this->composer['repositories']) || !is_array($this->composer['repositories'])) {
            $this->composer['repositories'] = [];
        }

        // Normalize path
        $url = rtrim($url, '/');

        // Check if already exists
        foreach ($this->composer['repositories'] as $repo) {
            if (($repo['type'] ?? '') === 'path' && ($repo['url'] ?? '') === $url) {
                // Already exists, skip
                return;
            }
        }

        // Add new repository entry
        $this->composer['repositories'][] = [
            'type' => 'path',
            'url' => $url,
            'options' => [
                'symlink' => true,
            ],
        ];

        $this->save();
    }

    /**
     * Ensure minimum-stability is set (default: dev).
     */
    public function ensureMinimumStability(string $stability = 'dev'): void
    {
        $this->composer['minimum-stability'] = $stability;
        $this->save();
    }

    protected function save(): void
    {
        $json = json_encode($this->composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        app(BackupOriginalFileAction::class)($this->path);
        file_put_contents($this->path, $json);
    }
}
