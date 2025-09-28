<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[ActionDescription(
    name: 'generate_enums',
    description: 'Generates PHP and TS enums for all enum fields in entities using stubs.'
)]
class GenerateEnumsAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected string $phpEnumPath;

    protected string $tsEnumPath;

    protected string $stubsDir;

    private BuildContext $context;

    public function __construct()
    {
        $this->phpEnumPath = app_path('Enums');
        $this->tsEnumPath = resource_path('js/enums');
        $this->stubsDir = __DIR__.'/../../../stubs/enums';

        if (! File::exists($this->phpEnumPath)) {
            File::makeDirectory($this->phpEnumPath, 0755, true);
        }

        if (! File::exists($this->tsEnumPath)) {
            File::makeDirectory($this->tsEnumPath, 0755, true);
        }
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->context = $context;
        $this->logInvocation($this->describe()->name);

        foreach ($context->getConfig()->entities as $entity) {
            foreach ($entity->getFields() as $field) {
                if ($field->isEnum()) {
                    $this->generateEnum($entity->getName(), $field);
                }
            }
        }

        return $context;
    }

    /**
     * Generates PHP and TS enum files for a given entity field.
     */
    protected function generateEnum(string $entityName, Field $field): void
    {
        $enumName = Str::studly($entityName).Str::studly($field->name).'Enum';
        $phpFile = "{$this->phpEnumPath}/{$enumName}.php";
        $tsFile = "{$this->tsEnumPath}/{$enumName}.ts";

        // --- PHP Enum ---
        $enumStub = File::get($this->stubsDir.'/enum.php.stub');

        $cases = '';
        $labels = '';
        foreach ($field->options as $value) {
            $caseName = $value->label;
            $cases .= "    case {$caseName} = '{$value->name}';\n";
            $labels .= "        self::{$caseName}->value => '{$caseName}',\n";
        }

        $phpContent = str_replace(
            ['{{enumName}}', '{{cases}}', '{{labels}}'],
            [$enumName, $cases, $labels],
            $enumStub
        );

        // Call action - write file and log
        app(GenerateFileAction::class)($phpFile, $phpContent);
        $this->context->registerGeneratedFile($phpFile);
        Log::channel('magic')->info("Generated PHP enum: {$enumName}");

        // --- TS Enum ---
        $tsStub = File::get($this->stubsDir.'/enum.ts.stub');

        $tsCases = '';
        $tsLabels = '';
        foreach ($field->options as $value) {
            $caseName = $value->label;
            $tsCases .= "    {$caseName}: '{$value->name}',\n";
            $tsLabels .= "    [{$enumName}.{$caseName}]: '{$caseName}',\n";
        }

        $tsContent = str_replace(
            ['{{enumName}}', '{{cases}}', '{{labels}}'],
            [$enumName, $tsCases, $tsLabels],
            $tsStub
        );

        app(GenerateFileAction::class)($tsFile, $tsContent);
        $this->context->registerGeneratedFile($tsFile);
        Log::channel('magic')->info("Generated TS enum: {$enumName}");
    }
}
