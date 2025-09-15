<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Glugox\ModelMeta\Fields\Boolean;
use Glugox\ModelMeta\Fields\Date;
use Glugox\ModelMeta\Fields\DateTime;
use Glugox\ModelMeta\Fields\Email;
use Glugox\ModelMeta\Fields\Enum;
use Glugox\ModelMeta\Fields\File;
use Glugox\ModelMeta\Fields\Id;
use Glugox\ModelMeta\Fields\Image;
use Glugox\ModelMeta\Fields\Number;
use Glugox\ModelMeta\Fields\Password;
use Glugox\ModelMeta\Fields\Slug;
use Glugox\ModelMeta\Fields\Text;
use Illuminate\Support\Facades\File as FileFacade;

#[ActionDescription(
)]
class GenerateModelMetaAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * @var array|string[] Map of JSON field properties to fluent setter methods on field classes.
     */
    protected array $setterMap = [
        'required' => 'required',
        'nullable' => 'nullable',
        'unique' => 'unique',
        'sortable' => 'sortable',
        'searchable' => 'searchable',
        'hidden' => 'hidden',
        'default' => 'default',
        'min' => 'min',
        'max' => 'max',
        'step' => 'step',
        'main' => 'main',
    ];

    /**
     * @var array <string, int> Map of class basename to indicate which field classes need to be imported.
     */
    // Store uses
    protected array $uses = [];

    /**
     * Build all models based on the configuration.
     */
    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);
        foreach ($context->getConfig()->entities as $entity) {
            $this->generateForEntity($entity);
        }

        return $context;
    }

    protected function generateForEntity(Entity $entity): void
    {
        $this->uses = [];
        $fieldLines = array_map(fn ($field) => $this->buildFieldLine($field), $entity->getFields());

        $replacements = [
            'namespace' => 'App\\Meta\\Models',
            'className' => $entity->getName().'Meta',
            'fields' => implode("\n            ", $fieldLines),
            'importedFields' => implode(",\n    ", array_keys($this->uses)),
        ];

        // Load stub & apply replacements
        $stub = StubHelper::loadStub('meta/model/model-meta.stub', $replacements);

        $filePath = base_path("app/Meta/Models/{$entity->getName()}Meta.php");

        FileFacade::ensureDirectoryExists(dirname($filePath));
        FileFacade::put($filePath, $stub);
    }

    /**
     * Build a single field line for the fields() array.
     */
    protected function buildFieldLine(Field $field): string
    {

        $type = $field->type;
        $class = match ($type) {
            FieldType::ID => Id::class,
            FieldType::EMAIL => Email::class,
            FieldType::PASSWORD => Password::class,
            FieldType::ENUM => Enum::class,
            FieldType::DECIMAL, FieldType::INTEGER, FieldType::FLOAT => Number::class,
            FieldType::FILE => File::class,
            FieldType::IMAGE => Image::class,
            FieldType::BOOLEAN => Boolean::class,
            FieldType::DATETIME => DateTime::class,
            FieldType::DATE => Date::class,
            FieldType::SLUG => Slug::class,
            default => Text::class,
        };

        // Base field creation
        $code = $this->buildFieldCode($field, class_basename($class));
        $this->uses[class_basename($class)] = 1;

        $settersProcessed = [];
        // Process default required fields
        if ($field->is('required')  // explicitly required
            || ($field->is('main') && ! $field->is('nullable') && ! $field->is('hidden'))) { // name fields are required unless nullable or hidden
            $code .= '->required()';
            $settersProcessed[] = 'required';
        }

        // Apply fluent setters dynamically
        // E.g., ->required()->sortable()->default('value')
        foreach ($this->setterMap as $jsonKey => $setterMethod) {

            if (in_array($setterMethod, $settersProcessed)) {
                continue; // Skip already processed setters
            }
            if (! empty($field->is($jsonKey))) {
                if (in_array($setterMethod, ['default', 'maxLength', 'min', 'max', 'step'])) {

                    $fieldPropValue = $field->get($jsonKey);
                    $value = is_array($fieldPropValue) ? var_export($fieldPropValue, true) : $fieldPropValue;
                    $strValue = exportPhpValue($value);
                    $code .= "->{$setterMethod}({$strValue})";
                } else {
                    $code .= "->{$setterMethod}()";
                }
            }
        }

        return $code.',';
    }

    /**
     * Build the base field creation code, e.g., Text::make('field_name')
     */
    private function buildFieldCode(Field $field, string $class_basename): string
    {
        $code = "{$class_basename}::make('{$field->name}')";
        if ($field->type === FieldType::SLUG && ! empty($field->values)) {
            $strValues = exportPhpValue($field->values);
            $code .= "->sourceField('{$strValues}')";
        }

        return $code;
    }
}
