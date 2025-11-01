<?php

declare(strict_types=1);

namespace Glugox\Builder\Actions;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Concerns\AsDescribableAction;
use Glugox\Builder\Contracts\DescribableAction;
use Glugox\Builder\Dto\BuilderConfig;
use InvalidArgumentException;
use JsonException;

#[ActionDescription(
    name: 'load_magic_config',
    description: 'Loads a Magic JSON configuration file into an internal representation.',
    parameters: [
        'configPath' => 'Absolute path to the Magic JSON configuration file.',
    ],
)]
class LoadConfigAction implements DescribableAction
{
    use AsDescribableAction;

    public function __invoke(string $configPath): BuilderConfig
    {
        if ($configPath === '') {
            throw new InvalidArgumentException('The --config option is required.');
        }

        if (! is_file($configPath)) {
            throw new InvalidArgumentException('The provided configuration file could not be found.');
        }

        $contents = file_get_contents($configPath);
        if ($contents === false) {
            throw new InvalidArgumentException('Unable to read the configuration file.');
        }

        try {
            $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The configuration file is not valid JSON: '.$exception->getMessage());
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('The configuration file must decode into an array.');
        }

        return BuilderConfig::fromArray($payload);
    }
}
