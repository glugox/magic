<?php

namespace Glugox\Magic\Support\Config;

use Exception;
use Glugox\Magic\Helpers\EnumFieldOptionsParser;
use Glugox\Magic\Support\Config\Field\EnumFieldOption;
use Glugox\Magic\Support\TypeHelper;
use Glugox\ModelMeta\FieldType as ModelMetaFieldType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Represents a field in an entity.
 *
 * Encapsulates database + UI metadata: type, length, nullability,
 * default value, numeric ranges, and UI search/sort flags.
 */
class Field
{
    /**
     * Create a new Field instance.
     *
     * @param  string  $name  Field name
     * @param  FieldType  $type  Field type (enum)
     * @param  Entity|null  $entityRef  The entity this field belongs to
     * @param  bool  $nullable  Whether the field can be null
     * @param  bool  $sometimes  Validate only if set, can be unset
     * @param  int|null  $length  String length if applicable
     * @param  int|null  $precision  Numeric precision
     * @param  int|null  $scale  Numeric scale
     * @param  mixed|null  $default  Default value
     * @param  string|null  $comment  Optional database comment
     * @param  bool  $sortable  Whether sortable in UI
     * @param  bool  $searchable  Whether searchable in UI
     * @param  string[]  $options  Enum or option values
     * @param  int|null  $min  Minimum allowed numeric value
     * @param  int|null  $max  Maximum allowed numeric value
     */
    public function __construct(
        public string $name,                 // field name
        public FieldType $type,              // type enum
        public ?Entity $entityRef = null,    // reference to the parent entity
        public bool $required = false,       // is it required? (not nullable and not sometimes)
        public bool $nullable = false,       // can it be null?
        public bool $sometimes = false,      // Validate only if set, can be unset
        public ?int $length = null,          // string length if applicable
        public ?int $precision = null,       // numeric precision
        public ?int $scale = null,           // numeric scale
        public mixed $default = null,        // default value
        public ?string $comment = null,      // optional DB comment
        public bool $sortable = false,       // sortable in UI
        public bool $searchable = false,     // searchable in UI
        public bool $main = false,         // is this a main field, that is used for example to open the entity record when clicked on?
        public bool $showInTable = true,     // show in table views
        public bool $showInForm = true,      // show in forms
        public bool $hidden = false,      // hidden field, not shown in forms or tables
        /** @var EnumFieldOption[] Allowed enum/options */
        public array $options = [],
        public ?int $min = null,
        public ?int $max = null,
        public ?Relation $relation = null,
    ) {
        // Validate min/max values
        // $this->min = max(0.0, $this->min);
        // $this->max = max($this->min, $this->max); // ensure max >= min
    }

    /**
     * Create a Field from config array.
     *
     * @param array{
     *     name: string,
     *     type: string,
     *     required?: bool,
     *     nullable?: bool,
     *     sometimes?: bool,
     *     length?: int|null,
     *     precision?: int|null,
     *     scale?: int|null,
     *     default?: mixed,
     *     comment?: string|null,
     *     sortable?: bool,
     *     searchable?: bool,
     *     main?: bool,
     *     showInTable?: bool,
     *     showInForm?: bool,
     *     options?: array{
     *       string|array<string, string>
     *     },
     *     min?: int,
     *     max?: int
     * } $data
     */
    public static function fromConfig(array $data, ?Entity $entity = null): self
    {
        $type = FieldType::from($data['type']);

        return new self(
            name: $data['name'],
            type: $type,
            entityRef: $entity,
            required: $data['required'] ?? false,
            nullable: $data['nullable'] ?? false,
            sometimes: $data['sometimes'] ?? false,
            length: $data['length'] ?? null,
            precision: $data['precision'] ?? null,
            scale: $data['scale'] ?? null,
            default: $data['default'] ?? null,
            comment: $data['comment'] ?? null,
            sortable: $data['sortable'] ?? false,
            searchable: $data['searchable'] ?? false,
            main: $data['main'] ?? false,
            showInTable: $data['showInTable'] ?? true,
            showInForm: $data['showInForm'] ?? true,
            options: EnumFieldOptionsParser::parse($data['options'] ?? []),
            min: $data['min'] ?? null,
            max: $data['max'] ?? null,
            relation: null,
        );
    }

    /**
     * Creates a new field from relation.
     */
    public static function fromRelation(Relation $relation): self
    {
        // Determine field type based on relation type
        $fieldType = app(TypeHelper::class)
            ->relationTypeToFieldType($relation->getType());

        return new self(
            name: $relation->getRelationName(),
            type: $fieldType,
            entityRef: $relation->getLocalEntity(),
            nullable: false,
            sometimes: false,
            length: null,
            precision: null,
            scale: null,
            default: null,
            comment: 'Foreign key to '.$relation->getRelatedEntityName(),
            sortable: true,
            searchable: false,
            main: false,
            options: [],
            min: null,
            max: null,
            relation: $relation,
        );
    }

    /**
     * Return Entity this field belongs to.
     *
     * @return Entity|null The entity this field belongs to, or null if not set.
     */
    public function getEntity(): ?Entity
    {
        return $this->entityRef;
    }

    /**
     * Human-readable title of the field.
     */
    public function label(): string
    {
        return Str::title(Str::replace('_', ' ', $this->name));
    }

    /**
     * Returns the migration type string that is used in Laravel migrations. For example: $table->string('name').
     * In this case, it would return 'string'.
     */
    public function migrationType(): string
    {
        // Check for BelongsTo relation first
        if ($relation = $this->belongsTo()) {
            // If this field is a foreign key, we return the 'foreignKey' type
            return 'foreignId';
        }

        // Map non-migration types to proper migration types
        return match ($this->type) {
            // Semantic/UI types mapped to real migration columns
            FieldType::EMAIL,
            FieldType::USERNAME,
            FieldType::PASSWORD,
            FieldType::URL,
            FieldType::PHONE,
            FieldType::SLUG,
            FieldType::SECRET,
            FieldType::TOKEN,
            FieldType::FILE,
            FieldType::IMAGE => 'string',

            // Relations that should not generate columns directly

            // Default: use the enum value as-is
            default => $this->type->value,
        };
    }

    /**
     * Model meta's FieldType is used in real app, while Magic FieldType is used in code generation.
     * To generate , for example TS types in real app, we need to map Magic FieldType to ModelMeta FieldType.
     */
    public function modelMetaType(): ModelMetaFieldType
    {

        return match ($this->type) {
            FieldType::ID => ModelMetaFieldType::ID,
            FieldType::FOREIGN_ID => ModelMetaFieldType::FOREIGN_ID,
            FieldType::STRING => ModelMetaFieldType::STRING,
            FieldType::TEXT => ModelMetaFieldType::TEXT,
            FieldType::INTEGER => ModelMetaFieldType::INTEGER,
            FieldType::BIG_INTEGER => ModelMetaFieldType::BIG_INTEGER,
            FieldType::FLOAT => ModelMetaFieldType::FLOAT,
            FieldType::DOUBLE => ModelMetaFieldType::DOUBLE,
            FieldType::DECIMAL => ModelMetaFieldType::DECIMAL,
            FieldType::BOOLEAN => ModelMetaFieldType::BOOLEAN,
            FieldType::DATE => ModelMetaFieldType::DATE,
            FieldType::DATETIME => ModelMetaFieldType::DATETIME,
            FieldType::TIME => ModelMetaFieldType::TIME,
            FieldType::TIMESTAMP => ModelMetaFieldType::TIMESTAMP,
            FieldType::JSON => ModelMetaFieldType::JSON,
            FieldType::JSONB => ModelMetaFieldType::JSONB,
            FieldType::ENUM => ModelMetaFieldType::ENUM,
            FieldType::EMAIL => ModelMetaFieldType::EMAIL,
            FieldType::PASSWORD => ModelMetaFieldType::PASSWORD,
            FieldType::URL => ModelMetaFieldType::URL,
            FieldType::PHONE => ModelMetaFieldType::PHONE,
            FieldType::USERNAME => ModelMetaFieldType::USERNAME,
            FieldType::SLUG => ModelMetaFieldType::SLUG,
            FieldType::SECRET => ModelMetaFieldType::SECRET,
            FieldType::TOKEN => ModelMetaFieldType::TOKEN,
            FieldType::FILE => ModelMetaFieldType::FILE,
            FieldType::IMAGE => ModelMetaFieldType::IMAGE,
            FieldType::LONG_TEXT => ModelMetaFieldType::LONG_TEXT,
            FieldType::MEDIUM_TEXT => ModelMetaFieldType::MEDIUM_TEXT,
            FieldType::CHAR => ModelMetaFieldType::CHAR,
            FieldType::BINARY => ModelMetaFieldType::BINARY,
            FieldType::SMALL_INTEGER => ModelMetaFieldType::SMALL_INTEGER,
            FieldType::TINY_INTEGER => ModelMetaFieldType::TINY_INTEGER,
            FieldType::UNSIGNED_BIG_INTEGER => ModelMetaFieldType::UNSIGNED_BIG_INTEGER,
            FieldType::UNSIGNED_INTEGER => ModelMetaFieldType::UNSIGNED_INTEGER,
            FieldType::UNSIGNED_SMALL_INTEGER => ModelMetaFieldType::UNSIGNED_SMALL_INTEGER,
            FieldType::UNSIGNED_TINY_INTEGER => ModelMetaFieldType::UNSIGNED_TINY_INTEGER,
            FieldType::UUID => ModelMetaFieldType::UUID,
            FieldType::YEAR => ModelMetaFieldType::YEAR,
            // Relations

            default => ModelMetaFieldType::STRING,
        };
    }

    /**
     * Returns BelongsTo relation if this field is a foreign key.
     *
     * @return Relation|null The BelongsTo relation if this field is a foreign key, or null otherwise.
     */
    public function belongsTo(): ?Relation
    {
        if ($this->relation && $this->relation->getType() === RelationType::BELONGS_TO) {
            return $this->relation;
        }

        if ($this->entityRef === null) {
            return null; // No entity reference, cannot determine relation
        }

        /**
         * The rest of the code if for getting other relations by field,
         * but currently we only consider BelongsTo relations as foreign keys.
         * And if it catches morph relations, it would be incorrect and throw errors.
         */
        $relationField = $this->entityRef->getRelationByField($this);
        if ($relationField && $relationField->getType() === RelationType::BELONGS_TO) {
            return $relationField;
        }

        return null; // Not a foreign key field
    }

    /**
     * Get migration arguments for this field.
     *
     * @return array<string|int> Returns an array of arguments for the migration method.
     *                           For example, for a string field with length 255, it would return ['name', 255].
     *                           For an enum field with options, it would return ['name', ["pending", "processing", "shipped", "delivered"], ...].
     */
    public function migrationArgs(): array
    {
        $args = ["'{$this->name}'"]; // Start with the field name
        // Add length if applicable
        if ($this->length !== null) {
            $args[] = $this->length;
        }
        // Add precision and scale if applicable
        if ($this->precision !== null && $this->scale !== null) {
            $args[] = $this->precision;
            $args[] = $this->scale;
        }

        // Add enum options if applicable
        if ($this->isEnum() && ! empty($this->options)) {
            $args[] = '['.implode(', ', array_map(
                fn ($v) => "'".str_replace("'", "\\'", $v->name)."'",
                array_values($this->options)
            )).']';
        }

        return $args;
    }

    /**
     * @return string[] List of enum option names (keys/values).
     */
    public function getOptionsNames(): array
    {
        return array_map(fn (EnumFieldOption $opt) => $opt->name, $this->options);
    }

    /**
     * Type checks.
     */
    public function isEnum(): bool
    {
        return $this->type === FieldType::ENUM;
    }

    /**
     * Return true if the field is a BelongsTo relation.
     */
    public function isBelongsTo(): bool
    {
        return $this->belongsTo() !== null;
    }

    /**
     * Check if this field is an id field.
     */
    public function isId(): bool
    {
        return $this->name === 'id';
    }

    /**
     * Check if this field is a name field.
     */
    public function asMain(): self
    {
        $this->main = true;

        return $this;
    }

    /**
     * @return bool True if the field is main (name/title) field.
     */
    public function isMain(): bool
    {
        // Check if the field name is 'name' or 'title', which are common conventions for name fields
        return $this->main
            || in_array($this->name, ['name', 'title'], true)
            || Str::endsWith($this->name, '_name');
    }

    // Semantic checks for field types

    public function isDate(): bool
    {
        return $this->type === FieldType::DATE;
    }

    public function isDatetime(): bool
    {
        return $this->type === FieldType::DATETIME;
    }

    public function isPassword(): bool
    {
        return $this->type === FieldType::PASSWORD;
    }

    public function isTime(): bool
    {
        return $this->type === FieldType::TIME;
    }

    public function isTimestamp(): bool
    {
        return $this->type === FieldType::TIMESTAMP;
    }

    public function isJson(): bool
    {
        return in_array($this->type, [FieldType::JSON, FieldType::JSONB], true);
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, [
            FieldType::INTEGER,
            FieldType::BIG_INTEGER,
            FieldType::FLOAT,
            FieldType::DOUBLE,
            FieldType::DECIMAL,
        ], true);
    }

    /**
     * Check if the given flag/identifier is true for this field.
     *
     * Example:
     *   $field->is('required');   // true if required
     *   $field->is('nullable');   // true if nullable
     *   $field->is('id');         // true if field name is 'id'
     */
    public function is(string $key): bool
    {
        $key = Str::camel($key);

        // Semantic method, e.g. isMain(), isEnum(), isId()
        $method = 'is'.Str::studly($key);
        if (method_exists($this, $method)) {
            return (bool) $this->{$method}();
        }

        // Direct property
        if (property_exists($this, $key)) {
            $value = $this->{$key};

            return is_bool($value) ? $value : (bool) $value;
        }

        return false;
    }

    /**
     * Get the value of a property or semantic accessor.
     *
     * Example:
     *   $field->get('length');    // 255
     *   $field->get('default');   // some default value
     *   $field->get('main');    // true if it is a name field
     */
    public function get(string $key): mixed
    {
        $key = Str::camel($key);

        // Direct property
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        // Semantic method
        $method = Str::camel($key);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        // Semantic "isX" method fallback
        $isMethod = 'is'.Str::studly($key);
        if (method_exists($this, $isMethod)) {
            return $this->{$isMethod}();
        }

        return null;
    }

    /**
     * Debug log
     */
    public function debugLog(): void
    {
        Log::channel('magic')->debug($this->printDebug());
    }

    /**
     * Returns the string representation of the field for debugging.
     */
    public function printDebug(): string
    {
        $parts = [];

        // Always show name and type
        $parts[] = "name: {$this->name}";
        $parts[] = "type: {$this->type->value}";

        // Only show flags if true
        if ($this->nullable) {
            $parts[] = 'nullable';
        }
        if ($this->sometimes) {
            $parts[] = 'sometimes';
        }
        if ($this->sortable) {
            $parts[] = 'sortable';
        }
        if ($this->searchable) {
            $parts[] = 'searchable';
        }

        // Only show properties if they are set
        if ($this->length !== null) {
            $parts[] = "length: {$this->length}";
        }
        if ($this->precision !== null) {
            $parts[] = "precision: {$this->precision}";
        }
        if ($this->scale !== null) {
            $parts[] = "scale: {$this->scale}";
        }
        if ($this->default !== null) {
            $parts[] = 'default: '.json_encode($this->default);
        }
        if (! empty($this->comment)) {
            $parts[] = "comment: {$this->comment}";
        }
        if (! empty($this->options)) {
            $vals = implode(', ', array_map(fn ($v) => json_encode($v), $this->options));
            $parts[] = "options: [{$vals}]";
        }
        if ($this->min !== null) {
            $parts[] = "min: {$this->min}";
        }
        if ($this->max !== null) {
            $parts[] = "max: {$this->max}";
        }

        return 'Field('.implode(', ', $parts).')';
    }

    /**
     * Returns true if this field is a foreign key (i.e., belongs to another entity).
     * TODO: Make this more certain by checking actual relations.
     */
    public function isForeignKey(): bool
    {
        // Check if the field name ends with '_id' which is a common convention for foreign keys
        if (Str::endsWith($this->name, '_id')) {
            return true;
        }

        // Check if this field has a BelongsTo relation
        if ($this->belongsTo() !== null) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getFirstEnumOptionValue(): mixed
    {
        if ($this->type !== FieldType::ENUM) {
            throw new Exception('Called getFirstEnumOptionValue() on non enum field type');
        }

        return $this->options[0]->name ?? null;
    }

    /**
     * Json representation of the field.
     */
    public function toJson(): string
    {
        $json = json_encode([
            'name' => $this->name,
            'type' => $this->type->value,
            'nullable' => $this->nullable,
            'sometimes' => $this->sometimes,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'default' => $this->default,
            'comment' => $this->comment,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'showInTable' => $this->showInTable,
            'showInForm' => $this->showInForm,
            'options' => $this->options,
            'min' => $this->min,
            'max' => $this->max,
        ], JSON_PRETTY_PRINT);

        return $json ?: '{}';
    }
}
