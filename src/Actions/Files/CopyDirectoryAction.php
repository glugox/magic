<?php

namespace Glugox\Magic\Actions\Files;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'copy_directory_with_list',
    description: 'Copies all files from a source directory to a destination directory, preserving structure, and returns a list of copied files.',
    parameters: [
        'source' => 'The source directory path.',
        'destination' => 'The destination directory path.',
    ]
)]
class CopyDirectoryAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param  bool  $deleteExtraneous  When true, remove files and directories in the destination that do not exist in the
     *                                  source. Cleanup is skipped at the root level to avoid deleting vendor or other
     *                                  user-provided directories. Child directories are mirrored exactly.
     *
     * @return array<int, string> List of affected paths (copied, created or removed)
     */
    public function __invoke(string $source, string $destination, bool $deleteExtraneous = false, bool $backupExisting = false): array
    {
        if (! File::exists($source)) {
            Log::channel('magic')->warning("Source directory does not exist: {$source}");

            return [];
        }

        $affectedPaths = [];

        $this->syncDirectory(
            $source,
            $destination,
            $deleteExtraneous,
            $affectedPaths,
            $deleteExtraneous ? false : true,
            $destination,
            $backupExisting
        );

        return $affectedPaths;
    }

    /**
     * Mirror the source directory into the destination directory.
     *
     * @param  array<int, string>  $affectedPaths
     */
    private function syncDirectory(
        string $sourceDir,
        string $destinationDir,
        bool $deleteExtraneous,
        array &$affectedPaths,
        bool $cleanupCurrentLevel,
        string $destinationRoot,
        bool $backupExisting
    ): void {
        if (! File::exists($sourceDir)) {
            return;
        }

        if (! File::exists($destinationDir)) {
            File::ensureDirectoryExists($destinationDir);
            $affectedPaths[] = $destinationDir;
            Log::channel('magic')->info('---Created directory while copying: '.$this->relativeToDestination($destinationDir, $destinationRoot));
        }

        $sourceItems = array_values(array_diff(scandir($sourceDir) ?: [], ['.', '..']));

        foreach ($sourceItems as $item) {
            $sourcePath = $sourceDir.DIRECTORY_SEPARATOR.$item;
            $targetPath = $destinationDir.DIRECTORY_SEPARATOR.$item;

            if (File::isDirectory($sourcePath)) {
                if (! File::isDirectory($targetPath)) {
                    File::ensureDirectoryExists($targetPath);
                    $affectedPaths[] = $targetPath;
                    Log::channel('magic')->info('---Created directory while copying: '.$this->relativeToDestination($targetPath, $destinationRoot));
                }

                $this->syncDirectory(
                    $sourcePath,
                    $targetPath,
                    $deleteExtraneous,
                    $affectedPaths,
                    $deleteExtraneous,
                    $destinationRoot,
                    $backupExisting
                );

                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));
            if ($backupExisting) {
                app(BackupOriginalFileAction::class)($targetPath);
            }
            File::copy($sourcePath, $targetPath);
            $affectedPaths[] = $targetPath;
            Log::channel('magic')->info('---Copied file: '.$this->relativeToDestination($targetPath, $destinationRoot));
        }

        if (! $deleteExtraneous || ! $cleanupCurrentLevel) {
            return;
        }

        $destinationItems = array_values(array_diff(scandir($destinationDir) ?: [], ['.', '..']));

        foreach ($destinationItems as $item) {
            if (in_array($item, $sourceItems, true)) {
                continue;
            }

            $targetPath = $destinationDir.DIRECTORY_SEPARATOR.$item;
            $relativePath = $this->relativeToDestination($targetPath, $destinationRoot);

            if (File::isDirectory($targetPath)) {
                File::deleteDirectory($targetPath);
                Log::channel('magic')->info('---Removed directory: '.$relativePath);
            } else {
                File::delete($targetPath);
                Log::channel('magic')->info('---Removed file: '.$relativePath);
            }

            $affectedPaths[] = $targetPath;
        }
    }

    private function relativeToDestination(string $path, string $destinationRoot): string
    {
        return ltrim(str_replace($destinationRoot, '', $path), DIRECTORY_SEPARATOR);
    }
}
