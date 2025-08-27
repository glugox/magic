<?php

namespace Glugox\Magic\Actions\Files;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;

#[ActionDescription(
    name: 'copy_directory_with_list',
    description: 'Copies all files from a source directory to a destination directory, preserving structure, and returns a list of copied files.',
    parameters: [
        'source' => 'The source directory path.',
        'destination' => 'The destination directory path.'
    ]
)]
class CopyDirectoryAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param string $source
     * @param string $destination
     * @return array<string> List of copied file paths
     */
    public function __invoke(string $source, string $destination): array
    {
        $copiedFiles = [];

        $files = File::allFiles($source);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getRealPath(), $targetPath);

            $copiedFiles[] = $targetPath;
        }

        return $copiedFiles;
    }
}
