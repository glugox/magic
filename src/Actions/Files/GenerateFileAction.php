<?php


namespace Glugox\Magic\Actions\Files;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'generate_file',
    description: 'Generates a file with the given content and registers it as created or modified.',
    parameters: [
        'filePath' => 'The full path to the file.',
        'content' => 'The file contents.'
    ]
)]
class GenerateFileAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param string $filePath
     * @param string $content
     * @return string The generated file path
     */
    public function __invoke(string $filePath, string $content): string
    {
        $isUpdate = File::exists($filePath);

        Log::channel('magic')->info("Generating file: " . $filePath. ($isUpdate ? ' (updated)' : ' (new)'));

        // Ensure the directory exists
        $directory = dirname($filePath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($filePath, $content);

        return $filePath;
    }
}
