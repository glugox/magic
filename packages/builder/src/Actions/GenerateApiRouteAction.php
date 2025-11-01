<?php

declare(strict_types=1);

namespace Glugox\Builder\Actions;

use Glugox\Builder\Attributes\ActionDescription;
use Glugox\Builder\Concerns\AsDescribableAction;
use Glugox\Builder\Contracts\DescribableAction;
use Glugox\Builder\Dto\EntityDefinition;

#[ActionDescription(
    name: 'generate_api_route',
    description: 'Builds the API route file for the primary entity using the builder stub.',
    parameters: [
        'entity' => 'The entity definition used to populate the stub.',
    ],
)]
class GenerateApiRouteAction implements DescribableAction
{
    use AsDescribableAction;

    private const STUB_PATH = '/stubs/routes/api.php.stub';

    public function __construct(
        private ?RenderStubAction $renderStubAction = null,
    ) {
        $this->renderStubAction = $this->renderStubAction ?? new RenderStubAction();
    }

    public function __invoke(EntityDefinition $entity): string
    {
        $stubPath = dirname(__DIR__, 2).self::STUB_PATH;

        $fieldsBlock = $this->formatFields($entity->fieldNames());

        return ($this->renderStubAction)(
            $stubPath,
            [
                'route' => $entity->routeName(),
                'entity' => $entity->name,
                'fields' => $fieldsBlock,
            ],
        );
    }

    /**
     * @param  string[]  $fields
     */
    private function formatFields(array $fields): string
    {
        if ($fields === []) {
            return '        ';
        }

        $lines = array_map(
            static fn (string $field): string => "            '".str_replace("'", "\\'", $field)."',",
            $fields,
        );

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
