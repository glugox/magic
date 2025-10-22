<?php

namespace Glugox\Magic\Actions\Files;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

#[ActionDescription(
    name: 'backup_original_file',
    description: 'Stores a copy of an application file before it is modified by Magic so it can be restored later.',
    parameters: [
        'path' => 'Absolute path to the application file that should be backed up.',
    ]
)]
class BackupOriginalFileAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @return string|null The backup path if a backup was created or already exists, null when nothing was backed up.
     */
    public function __invoke(string $path): ?string
    {
        if (! File::exists($path)) {
            return null;
        }

        $realPath = realpath($path);
        $basePath = realpath(base_path());

        if ($realPath === false || $basePath === false) {
            throw new RuntimeException('Unable to resolve real path for backup.');
        }

        if (! str_starts_with($realPath, $basePath)) {
            Log::channel('magic')->warning("Skipping backup for path outside the application root: {$realPath}");

            return null;
        }

        $relativePath = ltrim(str_replace($basePath, '', $realPath), DIRECTORY_SEPARATOR);
        $backupRoot = base_path('.magic/backup');
        $backupPath = $backupRoot.DIRECTORY_SEPARATOR.$relativePath;

        if (File::exists($backupPath)) {
            return $backupPath;
        }

        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($realPath, $backupPath);
        Log::channel('magic')->info('Backed up original file: '.$relativePath);

        return $backupPath;
    }
}
