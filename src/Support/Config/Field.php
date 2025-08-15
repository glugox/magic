<?php

namespace Glugox\Magic\Support\Config;

/**
 * Represents a field in an entity.
 *
 * This class encapsulates the properties of a field such as its name, type,
 * whether it is nullable, and additional attributes like length, precision,
 * scale, default value, and comment.
 */
class Field
{
    // Define constants for field types
    const string TYPE_DATE = 'date';

    const string TYPE_DATETIME = 'dateTime';

    const string TYPE_TIME = 'time';

    const string TYPE_TIMESTAMP = 'timestamp';

    const string TYPE_BIG_INCREMENTS = 'bigIncrements';

    const string TYPE_UNSIGNED_BIG_INTEGER = 'unsignedBigInteger';

    const string TYPE_STRING = 'string';

    const string TYPE_TEXT = 'text';

    const string TYPE_LONG_TEXT = 'longText';

    const string TYPE_INTEGER = 'integer';

    const string TYPE_UNSIGNED_INTEGER = 'unsignedInteger';

    const string TYPE_SMALL_INTEGER = 'smallInteger';

    const string TYPE_UNSIGNED_SMALL_INTEGER = 'unsignedSmallInteger';

    const string TYPE_TINY_INTEGER = 'tinyInteger';

    const string TYPE_UNSIGNED_TINY_INTEGER = 'unsignedTinyInteger';

    const string TYPE_FLOAT = 'float';

    const string TYPE_DOUBLE = 'double';

    const string TYPE_DECIMAL = 'decimal';

    const string TYPE_BOOLEAN = 'boolean';

    const string TYPE_JSON = 'json';

    const string TYPE_JSONB = 'jsonb';

    const string TYPE_BINARY = 'binary';

    const string TYPE_UUID = 'uuid';

    const string TYPE_ENUM = 'enum';

    const string TYPE_GEOMETRY = 'geometry';

    const string TYPE_POINT = 'point';

    const string TYPE_LINESTRING = 'linestring';

    const string TYPE_POLYGON = 'polygon';

    const string TYPE_GEOGRAPHY = 'geography';

    /**
     * Create a new Field instance.
     *
     * @param  string  $name  The name of the field.
     * @param  string  $type  The type of the field (e.g., 'string', 'integer').
     * @param  bool  $nullable  Whether the field can be null.
     * @param  int|null  $length  The length of the field (if applicable).
     * @param  int|null  $precision  The precision of the field (if applicable).
     * @param  int|null  $scale  The scale of the field (if applicable).
     * @param  mixed|null  $default  The default value for the field.
     * @param  string|null  $comment  An optional comment for the field.
     * @param  bool|null  $sortable  Whether the field is sortable in UI.
     * @param  bool|null  $searchable  Whether the field is searchable in UI.
     * @param  string[]  $values  Additional options for the field.
     */
    public function __construct(
        private string $name,
        private string $type,
        private bool $nullable,
        private ?int $length = null,
        private ?int $precision = null,
        private ?int $scale = null,
        private mixed $default = null,
        private ?string $comment = null,
        private ?bool $sortable = false,
        private ?bool $searchable = false,
        private ?array $values = []
    ) {}

    /**
     * @return Field Generate Field object from an array of properties.
     */
    public static function fromConfig(array $data): self
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['nullable'] ?? false,
            $data['length'] ?? null,
            $data['precision'] ?? null,
            $data['scale'] ?? null,
            $data['default'] ?? null,
            $data['comment'] ?? null,
            $data['sortable'] ?? false,
            $data['searchable'] ?? false,
            $data['values'] ?? []
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string Title of the field, which is a human-readable name.
     */
    public function getTitle(): string
    {
        // Convert snake_case to Title Case
        return \Str::title(\Str::replace('_', ' ', $this->name));
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool Whether the field is an enum type.
     */
    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }

    /**
     * @return string[] Additional options for the field.
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function isSearchable(): ?bool
    {
        return $this->searchable;
    }

    public function isDate()
    {
        return $this->type === self::TYPE_DATE;
    }

    public function isDatetime()
    {
        return $this->type === self::TYPE_DATETIME;
    }

    public function isTime()
    {
        return $this->type === self::TYPE_TIME;
    }

    public function isTimestamp()
    {
        return $this->type === self::TYPE_TIMESTAMP;
    }
}
