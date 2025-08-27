<?php

namespace Glugox\Magic\Actions\Files;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Contracts\GeneratedFile;
use Glugox\Magic\Support\File\VueFile;
use Glugox\Magic\Traits\AsDescribableAction;


#[ActionDescription(
    name: 'write_generated_files',
    description: 'Writes generated files (like Vue components) to the filesystem',
    parameters: ['input' => 'The VueFile object containing file name and content to write']
)]
class WriteGeneratedFiles implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param VueFile $input JSON string or array
     * @return bool
     */
    public function __invoke(GeneratedFile $input): bool
    {
        $directory = __DIR__ . '/../../generated';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $input->fileName;
        return file_put_contents($filePath, (string)$input) !== false;
    }
}
