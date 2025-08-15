<?php

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Str;

/**
 * Represents a field in an entity.
 *
 * This class encapsulates the properties of a field such as its name, type,
 * whether it is nullable, and additional attributes like length, precision,
 * scale, default value, and comment.
 */
class Field
{
    /**
     * Create a new Field instance.
     *
     * @param string $name The name of the field.
     * @param FieldType $type The type of the field (e.g., 'string', 'integer').
     * @param bool $nullable Whether the field can be null.
     * @param int|null $length The length of the field (if applicable).
     * @param int|null $precision The precision of the field (if applicable).
     * @param int|null $scale The scale of the field (if applicable).
     * @param mixed|null $default The default value for the field.
     * @param string|null $comment An optional comment for the field.
     * @param bool|null $sortable Whether the field is sortable in UI.
     * @param bool|null $searchable Whether the field is searchable in UI.
     * @param string[] $values Additional options for the field.
     *
     */
    public function __construct(
        public string    $name,
        public FieldType $type,
        public bool      $nullable = false,
        public ?int      $length = null,
        public ?int      $precision = null,
        public ?int      $scale = null,
        public mixed     $default = null,
        public ?string   $comment = null,
        public ?bool     $sortable = false,
        public ?bool     $searchable = false,
        public array     $values = []
    ) {}

    /**
     * @return Field Generate Field object from an array of properties.
     */
    public static function fromConfig(array $data): self
    {
        return new self(
            $data['name'],
            FieldType::from($data['type']),
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

    /**
     * @return string Title of the field, which is a human-readable name.
     */
    public function getTitle(): string
    {
        // Convert snake_case to Title Case
        return Str::title(Str::replace('_', ' ', $this->name));
    }

    /**
     * @return bool Whether the field is an enum type.
     */
    public function isEnum(): bool
    {
        return $this->type === FieldType::ENUM;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isDate(): bool
    {
        return $this->type === FieldType::DATE;
    }

    public function isDatetime(): bool
    {
        return $this->type === FieldType::DATETIME;
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
        return in_array($this->type, [
            FieldType::JSON->value,
            FieldType::JSONB->value,
        ]);
    }
}
