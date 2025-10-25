<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Action as ConfigAction;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_action_commands',
    description: 'Generate application console commands for configured entity actions.',
    parameters: ['context' => 'The build context including entities and their configured actions.']
)]
class GenerateActionsAction implements DescribableAction
{
    use AsDescribableAction;
    use CanLogSectionTitle;

    protected BuildContext $context;

    /**
     * Where to place generated action classes
     */
    protected $actionsPath;

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        $this->actionsPath = MagicPaths::app('Actions');

        // Create App/Actions directory if it doesn't exist

        if (! File::exists($this->actionsPath)) {
            File::makeDirectory($this->actionsPath, 0755, true);
            Log::info("Created directory: {$this->actionsPath}");
        }

        $this->context = $context;

        foreach ($context->getConfig()->entities as $entity) {
            $this->generateForEntity($entity);
        }

        return $context;
    }

    protected function generateForEntity(Entity $entity): void
    {
        foreach ($entity->getActions() as $action) {
            $this->generateAction($entity, $action);
        }
    }

    protected function generateAction(Entity $entity, ConfigAction $action): void
    {
        $className = Str::studly(Str::replace('.', '_', $action->name)).'Action';
        $description = $action->description
            ?: sprintf('Handle the %s action for %s.', Str::headline($action->name), $entity->getName());

        $replacements = [
            'className' => $className,
            'description' => $description,
            'entityName' => $entity->getName(),
            'name' => $action->name,
            'label' => $action->label
        ];

        $stub = StubHelper::loadStub('actions/action.stub', $replacements);

        $filePath = $this->actionsPath.'/'.$className.'.php';

        app(GenerateFileAction::class)($filePath, $stub);
        $this->context->registerGeneratedFile($filePath);
    }
}
