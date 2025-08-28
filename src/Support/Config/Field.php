<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\TypeHelper;
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
     * @param  int|null  $length  String length if applicable
     * @param  int|null  $precision  Numeric precision
     * @param  int|null  $scale  Numeric scale
     * @param  mixed|null  $default  Default value
     * @param  string|null  $comment  Optional database comment
     * @param  bool  $sortable  Whether sortable in UI
     * @param  bool  $searchable  Whether searchable in UI
     * @param  string[]  $values  Enum or option values
     * @param  float  $min  Minimum allowed numeric value
     * @param  float  $max  Maximum allowed numeric value
     */
    public function __construct(
        public string $name,                 // field name
        public FieldType $type,              // type enum
        public ?Entity $entityRef = null,    // reference to the parent entity
        public bool $nullable = false,       // can it be null?
        public ?int $length = null,          // string length if applicable
        public ?int $precision = null,       // numeric precision
        public ?int $scale = null,           // numeric scale
        public mixed $default = null,        // default value
        public ?string $comment = null,      // optional DB comment
        public bool $sortable = false,       // sortable in UI
        public bool $searchable = false,     // searchable in UI
        public bool $isName = false,         // is this a name field, that is used for example to open the entity record when clicked on?
        public bool $showInTable = true,     // show in table views
        public bool $showInForm = true,      // show in forms
        /** @var string[] Allowed enum/options */
        public array $values = [],
        public $min = null,
        public $max = null,
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
     *     nullable?: bool,
     *     length?: int|null,
     *     precision?: int|null,
     *     scale?: int|null,
     *     default?: mixed,
     *     comment?: string|null,
     *     sortable?: bool,
     *     searchable?: bool,
     *     isName?: bool,
     *     showInTable?: bool,
     *     showInForm?: bool,
     *     values?: string[],
     *     min?: float,
     *     max?: float
     * } $data
     */
    public static function fromConfig(array $data, ?Entity $entity = null): self
    {
        $type = FieldType::from($data['type']);

        return new self(
            name: $data['name'],
            type: $type,
            entityRef: $entity,
            nullable: $data['nullable'] ?? false,
            length: $data['length'] ?? null,
            precision: $data['precision'] ?? null,
            scale: $data['scale'] ?? null,
            default: $data['default'] ?? null,
            comment: $data['comment'] ?? null,
            sortable: $data['sortable'] ?? false,
            searchable: $data['searchable'] ?? false,
            isName: $data['isName'] ?? false,
            showInTable: $data['showInTable'] ?? true,
            showInForm: $data['showInForm'] ?? true,
            values: $data['values'] ?? [],
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
            length: null,
            precision: null,
            scale: null,
            default: null,
            comment: 'Foreign key to '.$relation->getEntityName(),
            sortable: true,
            searchable: false,
            isName: false,
            values: [],
            min: null,
            max: null,
            relation: $relation,
        );
    }

    /**
     * Human-readable title of the field.
     */
    public function title(): string
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

        return match ($this->type) {
            FieldType::IMAGE, FieldType::URL, FieldType::PASSWORD => 'string',
            default => $this->type->value // Fallback to the enum value if not matched
        };
    }

    /**
     * Get migration arguments for this field.
     *
     * @return array<string|int> Returns an array of arguments for the migration method.
     *                           For example, for a string field with length 255, it would return ['name', 255].
     *                           For an enum field with values, it would return ['name', ["pending", "processing", "shipped", "delivered"], ...].
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

        // Add enum values if applicable
        if ($this->isEnum() && ! empty($this->values)) {
            $args[] = '['.implode(', ', array_map(
                fn ($v) => "'".str_replace("'", "\\'", $v)."'",
                array_values($this->values)
            )).']';
        }

        return $args;
    }

    /**
     * Returns BelongsTo relation if this field is a foreign key.
     */
    public function belongsTo(): ?Relation
    {
        if ($this->relation && in_array($this->relation->getType(), [RelationType::BELONGS_TO, RelationType::MORPH_TO], true)) {
            return $this->relation;
        }

        if ($this->entityRef === null) {
            return null; // No entity reference, cannot determine relation
        }
        // Check if the field name ends with '_id' which is a common convention for foreign keys
        // if (Str::endsWith($this->name, '_id')) {
        // Find the related entity in the entity reference
        $relationField = $this->entityRef->getRelationByField($this);

        // }

        return $relationField; // Not a foreign key field
    }

    /**
     * Return true if the field is a BelongsTo relation.
     */
    public function isBelongsTo(): bool
    {
        return $this->belongsTo() !== null;
    }

    /**
     * Check if this field is a name field.
     */
    public function isName(): bool
    {
        // Check if the field name is 'name' or 'title', which are common conventions for name fields
        return $this->isName
            || in_array($this->name, ['name', 'title'], true)
            || Str::endsWith($this->name, '_name');
    }

    /**
     * Type checks.
     */
    public function isEnum(): bool
    {
        return $this->type === FieldType::ENUM;
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
     * Returns the string representation of the field for debugging.
     */
    public function printDebug(): string
    {
        return sprintf(
            'Field(name: %s, type: %s, nullable: %s, length: %s, precision: %s, scale: %s, default: %s, comment: %s, sortable: %s, searchable: %s, values: [%s], min: %s, max: %s)',
            $this->name,
            $this->type->value,
            $this->nullable ? 'true' : 'false',
            $this->length ?? 'null',
            $this->precision ?? 'null',
            $this->scale ?? 'null',
            json_encode($this->default),
            $this->comment ?? 'null',
            $this->sortable ? 'true' : 'false',
            $this->searchable ? 'true' : 'false',
            implode(', ', array_map(fn ($v) => json_encode($v), $this->values)),
            $this->min ?? 'null',
            $this->max ?? 'null'
        );
    }

    /**
     * Debug log
     */
    public function debugLog(): void
    {
        Log::channel('magic')->debug($this->printDebug());
    }

    /**
     * Returns true if this field is a foreign key (i.e., belongs to another entity).
     * TODO: Make this more certain by checking actual relations.
     */
    public function isForeignKey()
    {
        // Check if the field name ends with '_id' which is a common convention for foreign keys
        if (Str::endsWith($this->name, '_id')) {
            return true;
        }
    }

    /**
     * Json representation of the field.
     */
    public function toJson(): string
    {
        return json_encode([
            'name' => $this->name,
            'type' => $this->type->value,
            'nullable' => $this->nullable,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'default' => $this->default,
            'comment' => $this->comment,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'showInTable' => $this->showInTable,
            'showInForm' => $this->showInForm,
            'values' => $this->values,
            'min' => $this->min,
            'max' => $this->max,
        ], JSON_PRETTY_PRINT);

    }
}
