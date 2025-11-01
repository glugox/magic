<?php

declare(strict_types=1);

namespace Glugox\Builder\Actions;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Concerns\AsDescribableAction;
use Glugox\Builder\Contracts\DescribableAction;
use InvalidArgumentException;

#[ActionDescription(
    name: 'render_stub',
    description: 'Renders a stub template replacing tokens with provided values.',
    parameters: [
        'stubPath' => 'Absolute path to the stub file.',
        'replacements' => 'Associative array of placeholder => value pairs.',
    ],
)]
class RenderStubAction implements DescribableAction
{
    use AsDescribableAction;

    /**
     * @param  array<string, string>  $replacements
     */
    public function __invoke(string $stubPath, array $replacements): string
    {
        if (! is_file($stubPath)) {
            throw new InvalidArgumentException('The requested stub file does not exist.');
        }

        $contents = file_get_contents($stubPath);
        if ($contents === false) {
            throw new InvalidArgumentException('Unable to read stub file: '.$stubPath);
        }

        foreach ($replacements as $placeholder => $value) {
            $contents = str_replace('{{'.$placeholder.'}}', $value, $contents);
        }

        return $contents;
    }
}
