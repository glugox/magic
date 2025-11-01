<?php

declare(strict_types=1);

namespace Glugox\Builder\Actions;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Concerns\AsDescribableAction;
use Glugox\Builder\Contracts\DescribableAction;
use RuntimeException;

#[ActionDescription(
    name: 'write_file',
    description: 'Writes generated contents to disk, ensuring the directory exists.',
    parameters: [
        'path' => 'Absolute path where the file will be written.',
        'contents' => 'The contents that should be saved.',
    ],
)]
class WriteFileAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(string $path, string $contents): string
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            $created = @mkdir($directory, 0o755, true);
            if (! $created && ! is_dir($directory)) {
                throw new RuntimeException('Unable to create directory: '.$directory);
            }
        }

        $bytes = file_put_contents($path, $contents);
        if ($bytes === false) {
            throw new RuntimeException('Failed to write file: '.$path);
        }

        return $path;
    }
}
