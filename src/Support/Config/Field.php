<?php

namespace Glugox\Magic\Support\Config;

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
     * @param string $name Field name
     * @param FieldType $type Field type (enum)
     * @param bool $nullable Whether the field can be null
     * @param int|null $length String length if applicable
     * @param int|null $precision Numeric precision
     * @param int|null $scale Numeric scale
     * @param mixed|null $default Default value
     * @param string|null $comment Optional database comment
     * @param bool $sortable Whether sortable in UI
     * @param bool $searchable Whether searchable in UI
     * @param string[] $values Enum or option values
     * @param float $min Minimum allowed numeric value
     * @param float $max Maximum allowed numeric value
     */
    public function __construct(
        public string $name,                 // field name
        public FieldType $type,              // type enum
        public bool $nullable = false,       // can it be null?
        public ?int $length = null,          // string length if applicable
        public ?int $precision = null,       // numeric precision
        public ?int $scale = null,           // numeric scale
        public mixed $default = null,        // default value
        public ?string $comment = null,      // optional DB comment
        public bool $sortable = false,       // sortable in UI
        public bool $searchable = false,     // searchable in UI
        /** @var string[] Allowed enum/options */
        public array $values = [],
        /** @var float Minimum allowed numeric value */
        public float $min = 0.0,
        /** @var float Maximum allowed numeric value */
        public float $max = 0.0
    ) {
        // Validate min/max values
        $this->min = max(0.0, $this->min);
        $this->max = max($this->min, $this->max); // ensure max >= min
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
     *     values?: string[],
     *     min?: float,
     *     max?: float
     * } $data
     */
    public static function fromConfig(array $data): self
    {
        return new self(
            name: $data['name'],
            type: FieldType::from($data['type']),
            nullable: $data['nullable'] ?? false,
            length: $data['length'] ?? null,
            precision: $data['precision'] ?? null,
            scale: $data['scale'] ?? null,
            default: $data['default'] ?? null,
            comment: $data['comment'] ?? null,
            sortable: $data['sortable'] ?? false,
            searchable: $data['searchable'] ?? false,
            values: $data['values'] ?? [],
            min: $data['min'] ?? 0.0,
            max: $data['max'] ?? 0.0,
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
     * Type checks.
     */
    public function isEnum(): bool
    {
        return $this->type === FieldType::ENUM;
    }

    // Semantic checks for field types
    public function isDate(): bool { return $this->type === FieldType::DATE; }
    public function isDatetime(): bool { return $this->type === FieldType::DATETIME; }
    public function isPassword(): bool { return $this->type === FieldType::PASSWORD; }
    public function isTime(): bool { return $this->type === FieldType::TIME; }
    public function isTimestamp(): bool { return $this->type === FieldType::TIMESTAMP; }
    public function isJson(): bool { return in_array($this->type, [FieldType::JSON, FieldType::JSONB], true); }
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
}
