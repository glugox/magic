<?php

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Str;

class Relation
{
    private RelationType $type;      // e.g. 'hasMany', 'belongsTo'

    private ?string $entityName;    // related entity name

    private Entity $localEntity;   // local entity for this relation

    private ?string $foreignKey;

    private ?string $localKey;

    private ?string $relationName; // e.g. 'posts', 'comments', etc.

    // getters ...
    public function __construct(
        RelationType|string $type,
        Entity $localEntity,
        ?string $entityName = null,
        ?string $foreignKey = null,
        ?string $localKey = null,
        ?string $relationName = null,
    ) {
        $this->type = RelationType::from($type);
        $this->localEntity = $localEntity;
        $this->entityName = $entityName;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->relationName = $relationName;
    }

    public function getType(): RelationType
    {
        return $this->type;
    }

    /**
     * @return Entity Returns the local entity for this relation.
     */
    public function getLocalEntity(): Entity
    {
        return $this->localEntity;
    }

    /**
     * @return bool Returns true if the relation is of type 'hasMany'.
     */
    public function isHasMany(): bool
    {
        return $this->type === RelationType::HAS_MANY;
    }

    /**
     * @return bool Returns true if the relation is of type 'hasOne'.
     */
    public function isHasOne(): bool
    {
        return $this->type === RelationType::HAS_ONE;
    }

    /**
     * @return bool Returns true if the relation is of type 'hasMany'.
     */
    public function isManyToMany(): bool
    {
        return $this->type === RelationType::BELONGS_TO_MANY;
    }

    /**
     * @return bool Returns true if the relation is of type 'belongsTo'.
     */
    public function isBelongsTo(): bool
    {
        return $this->type === RelationType::BELONGS_TO;
    }

    /**
     * @return true if the relation is of type 'BelongsToMany'.
     */
    public function isBelongsToMany(): bool
    {
        return $this->type === RelationType::BELONGS_TO_MANY;
    }

    /**
     * Get the name of the related entity. Ex. 'User', 'Post', etc.
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function getTableName(): string
    {
        // Convert entity name to snake_case for table name
        return Str::snake(Str::plural($this->entityName));
    }

    /**
     * Get href for the related entity.
     */
    public function getHref(): string
    {
        // Convert entity name to kebab-case for href
        return $this->getTableName();
    }

    /**
     * Get the name of the local table in snake_case. Ex. 'users', 'posts', etc.
     */
    public function getLocalTableName(): string
    {
        return $this->getLocalEntity()->getTableName();
    }

    /**
     * Get the name of the relation in camelCase. Ex. 'posts', 'comments', etc.
     */
    public function getRelationName(): string
    {
        // Lowercase and pluralize
        return $this->relationName ??= Str::plural(Str::camel($this->entityName));
    }

    /**
     * @return string Returns the name of the pivot table for many-to-many relations.
     */
    public function getPivotName(): string
    {
        // alphabetical order of related tables (Laravel convention)
        $tables = [
            Str::snake($this->localEntity->getName()),
            Str::snake($this->entityName),
        ];
        sort($tables);

        return implode('_', $tables);
    }

    /**
     * Returns the foreign key for this relation.
     * If not set, it defaults to snake_case of the entity name + '_id'.
     * For example, if the entity name is 'Post', the foreign key will be 'post_id'.
     * If the relation is polymorphic, it will return null.
     */
    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): ?string
    {
        return $this->localKey ??= Str::snake($this->entityName).'_id';
    }

    public function getMorphName(): ?string
    {
        // For polymorphic relations, return the relation name
        return $this->getRelationName();
    }
}
