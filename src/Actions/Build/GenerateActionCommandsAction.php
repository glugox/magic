<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Action as ConfigAction;
use Glugox\Magic\Support\Config\Entity;
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
class GenerateActionCommandsAction implements DescribableAction
{
    use AsDescribableAction;
    use CanLogSectionTitle;

    protected BuildContext $context;

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        $this->context = $context;

        foreach ($context->getConfig()->entities as $entity) {
            $this->generateForEntity($entity);
        }

        return $context;
    }

    protected function generateForEntity(Entity $entity): void
    {
        foreach ($entity->getActions() as $action) {
            if ($action->type !== 'command') {
                continue;
            }

            $this->generateCommandForAction($entity, $action);
        }
    }

    protected function generateCommandForAction(Entity $entity, ConfigAction $action): void
    {
        $signature = 'app-' . $action->command;

        if ($signature === null || $signature === '') {
            Log::channel('magic')->warning(sprintf(
                'Skipping action "%s" for entity "%s" because no command signature is defined.',
                $action->name,
                $entity->getName()
            ));

            return;
        }

        $className = $entity->getName() .  Str::studly($action->name).'Action';
        $namespace = 'App\Console\Commands';
        $description = $action->description
            ?: sprintf('Handle the %s action for %s.', Str::headline($action->name), $entity->getName());

        $replacements = [
            'namespace' => $namespace,
            'className' => $className,
            'signature' => $signature,
            'description' => addslashes($description),
            'actionNameHeadline' => Str::headline($action->name),
            'entityName' => $entity->getName(),
        ];

        $stub = StubHelper::loadStub('commands/actions/action-command.stub', $replacements);

        $filePath = app_path('Console/Commands/'.$className.'.php');
        File::ensureDirectoryExists(dirname($filePath));

        app(GenerateFileAction::class)($filePath, $stub);
        $this->context->registerGeneratedFile($filePath);
    }
}
