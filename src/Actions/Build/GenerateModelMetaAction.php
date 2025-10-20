<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\Config\Action;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Filter;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
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
use Glugox\ModelMeta\Filters\BelongsToFilter;
use Glugox\ModelMeta\Filters\BelongsToManyFilter;
use Glugox\ModelMeta\Filters\BooleanFilter;
use Glugox\ModelMeta\Filters\DateFilter;
use Glugox\ModelMeta\Filters\EnumFilter;
use Glugox\ModelMeta\Filters\HasManyFilter;
use Glugox\ModelMeta\Filters\HasOneFilter;
use Glugox\ModelMeta\Filters\NumberFilter;
use Glugox\ModelMeta\Filters\RangeFilter;
use Glugox\ModelMeta\Filters\TextFilter;
use Glugox\ModelMeta\Relations\BelongsTo;
use Glugox\ModelMeta\Relations\BelongsToMany;
use Glugox\ModelMeta\Relations\HasMany;
use Glugox\ModelMeta\Relations\HasOne;
use Glugox\ModelMeta\Relations\MorphedByMany;
use Glugox\ModelMeta\Relations\MorphMany;
use Glugox\ModelMeta\Relations\MorphOne;
use Glugox\ModelMeta\Relations\MorphTo;
use Glugox\ModelMeta\Relations\MorphToMany;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[ActionDescription(
    name: 'Generate Model Meta Classes',
    description: 'Generate model meta classes based on configuration'
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
    protected array $usesFields = [];

    /**
     * @var array <string, int> Map of class basename to indicate which relation classes need to be imported.
     */
    protected array $usesRelations = [];

    /**
     * @var array <string, int> Map of class basename to indicate which filter classes need to be imported.
     */
    protected array $usesFilters = [];

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
        $this->usesFields = [];
        $this->usesRelations = [];
        $this->usesFilters = [];

        $actionsLines = array_map(fn ($action) => $this->buildActionLine($action), $entity->getActions());

        $fieldLines = array_map(fn ($field) => $this->buildFieldLine($field), $entity->getFields());
        $relationsLines = array_map(fn ($relation) => $this->buildRelationLine($relation), $entity->getRelations());
        $filtersLines = array_map(fn ($filter) => $this->buildFilterLine($filter), $entity->getFilters());

        $replacements = [
            'namespace' => 'App\\Meta\\Models',
            'className' => $entity->getName().'Meta',
            'tableName' => $entity->getTableName(),
            'fields' => implode("\n            ", $fieldLines),
            'relations' => implode("\n            ", $relationsLines),
            'filters' => implode("\n            ", $filtersLines),
            'actions' => implode("\n            ", $actionsLines),
            'importedFields' => implode(",\n    ", array_keys($this->usesFields)),
            'importedRelations' => implode("\n", array_map(fn ($relationClass) => 'use '.$relationClass.';', array_keys($this->usesRelations))),
            'importedFilters' => implode("\n", array_map(fn ($filterClass) => 'use '.$filterClass.';', array_keys($this->usesFilters))),
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
        $this->usesFields[class_basename($class)] = 1;

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
     * Build a single relation line for the relations() array.
     */
    protected function buildRelationLine(Relation $relation): string
    {
        $type = $relation->type;
        $class = match ($type) {
            RelationType::BELONGS_TO => BelongsTo::class,
            RelationType::HAS_MANY => HasMany::class,
            RelationType::HAS_ONE => HasOne::class,
            RelationType::BELONGS_TO_MANY => BelongsToMany::class,
            RelationType::MORPH_TO => MorphTo::class,
            RelationType::MORPH_MANY => MorphMany::class,
            RelationType::MORPH_ONE => MorphOne::class,
            RelationType::MORPH_TO_MANY => MorphToMany::class,
            RelationType::MORPHED_BY_MANY => MorphedByMany::class,
            default => throw new InvalidArgumentException("Unsupported relation type: {$type->value}"),
        };

        $class_basename = class_basename($class);
        // Base relation creation
        $code = "{$class_basename}::make('{$relation->getRelationName()}')";
        $this->usesRelations[$class] = 1;
        // Apply eager fields if any
        if (! empty($relation->getEagerFieldsStr())) {
            $strEagerFields = exportPhpValue($relation->getEagerFieldsStr());
            $code .= "->eagerFields({$strEagerFields})";
        }

        return $code.',';
    }

    /**
     * Build a single filter line for the filters() array.
     */
    protected function buildFilterLine(Filter $filter): string
    {

        $class = match ($filter->type->value) {
            'text' => TextFilter::class,
            'enum' => EnumFilter::class,
            'date', 'datetime', 'date_range' => DateFilter::class,
            'range' => RangeFilter::class,
            'number' => NumberFilter::class,
            'boolean' => BooleanFilter::class,
            'belongs_to' => BelongsToFilter::class,
            'belongs_to_many' => BelongsToManyFilter::class,
            'has_one' => HasOneFilter::class,
            'has_many' => HasManyFilter::class,
            default => null,
        };

        $class_basename = class_basename($class);
        if ($class) {
            $code = "{$class_basename}::make('{$filter->field}')";
            $this->usesFilters[$class] = 1;

            // Label
            $strLabel = $filter->label ?? Str::title($filter->field);
            $code .= "->label('{$strLabel}')";

            return $code.',';
        }

        return '//TODO : '.$filter->toString();
    }

    /**
     * Build the base field creation code, e.g., Text::make('field_name')
     */
    private function buildFieldCode(Field $field, string $class_basename): string
    {
        $code = "{$class_basename}::make('{$field->name}')";
        if ($field->type === FieldType::SLUG && ! empty($field->options)) {
            $strValues = exportPhpValue($field->options);
            $code .= "->sourceField('{$strValues}')";
        }

        return $code;
    }

    /**
     * Build a single action definition entry for the actions() array.
     */
    protected function buildActionLine(Action $action): string
    {
        $array = $action->toArray();

        return exportPhpValue($array, 3).',';
    }
}
