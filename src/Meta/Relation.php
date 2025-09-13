<?php

namespace App\Meta;

use App\Meta\RelationType as RelationTypeEnum;

/**
 * Represents a model relationship.
 */
class Relation
{
    /**
     * Relation type, must be a RelationTypeEnum enum instance
     */
    public RelationTypeEnum $type;

    /**
     * Related model class
     */
    public string $related;

    /**
     * Name of the relation (used for dynamic access in Eloquent)
     */
    public string $name;

    /**
     * Local foreign key (optional, defaults to `name.'_id'`)
     */
    public ?string $foreignKey;

    /**
     * Extra options (pivot table, morph name, etc.)
     */
    public array $options;

    public function __construct(
        RelationTypeEnum $type,
        string $related,
        string $name,
        ?string $foreignKey = null,
        array $options = []
    ) {
        $this->type = $type;
        $this->related = $related;
        $this->name = $name;
        $this->foreignKey = $foreignKey;
        $this->options = $options;
    }

    // ---------- Factory for string input ----------
    public static function fromString(
        string $type,
        string $related,
        string $name,
        ?string $foreignKey = null,
        array $options = []
    ): self {
        return new self(RelationTypeEnum::from($type), $related, $name, $foreignKey, $options);
    }

    // ---------- Helper methods ----------

    public function isBelongsTo(): bool
    {
        return $this->is(RelationTypeEnum::BELONGS_TO);
    }

    public function is(RelationTypeEnum $type): bool
    {
        return $this->type === $type;
    }

    public function isHasOne(): bool
    {
        return $this->is(RelationTypeEnum::HAS_ONE);
    }

    public function isHasMany(): bool
    {
        return $this->is(RelationTypeEnum::HAS_MANY);
    }

    public function isBelongsToMany(): bool
    {
        return $this->is(RelationTypeEnum::BELONGS_TO_MANY);
    }

    public function isMorph(): bool
    {
        return in_array(
            $this->type,
            [
                RelationTypeEnum::MORPH_ONE,
                RelationTypeEnum::MORPH_MANY,
                RelationTypeEnum::MORPH_TO,
                RelationTypeEnum::MORPH_TO_MANY,
                RelationTypeEnum::MORPHED_BY_MANY
            ],
            true
        );
    }

    /**
     * Get effective foreign key
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey ?? $this->name.'_id';
    }
}
