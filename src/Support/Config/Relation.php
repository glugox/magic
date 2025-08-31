<?php

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Str;

class Relation
{
    public RelationType $type;      // e.g. 'hasMany', 'belongsTo'


    // getters ...
    public function __construct(
        RelationType|string      $type,
        private readonly Entity  $localEntity,
        private readonly ?string $entityName = null,
        private ?Entity          $relatedEntity = null,
        private readonly ?string $foreignKey = null,
        private ?string          $localKey = null,
        private ?string          $relationName = null,
    ) {
        $this->type = $type instanceof RelationType ? $type : RelationType::from($type);
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
    public function getRelatedEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * Sets the related entity
     */
    public function setRelatedEntity(Entity $entity): void
    {
        $this->relatedEntity = $entity;
    }

    /**
     * Get the related entity object.
     * If the related entity is not set, it throws an exception.
     *
     * @throws \RuntimeException
     */
    public function getRelatedEntity(): Entity
    {
        if (! $this->relatedEntity) {
            throw new \RuntimeException("Related entity is not set for relation of type {$this->type->value}");
        }
        return $this->relatedEntity;
    }

    /**
     * Get the name of the related table in snake_case. Ex. 'users', 'posts', etc.
     * // It differs from relation name which is camelCase and pluralized.
     */
    public function getTableName(): string
    {

        // If entity name is not set, return empty string
        if (! $this->entityName) {
           // If relation is MorphTo, return local entity's table name
           if ($this->type === RelationType::MORPH_TO) {
               return $this->getLocalEntity()->getTableName();
           }

           throw new \RuntimeException("Entity name is not set for relation of type {$this->type->value}");
        }

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
     * Returns controller name for the related entity.
     * // we need to be able to easily generate these php lines in order to write them to routes file
     * Route::get('users/{user}/roles', [\App\Http\Controllers\User\UserRoleController::class, 'edit'])
     * ->name('users.roles.edit');
     * Route::put('users/{user}/roles', [\App\Http\Controllers\User\UserRoleController::class, 'update'])
     * ->name('users.roles.update');
     */
    public function getControllerFullQualifiedName(): string
    {
        $localEntityName = $this->localEntity->getName();
        $relatedEntityName = $this->entityName;

        return "\\App\\Http\\Controllers\\{$localEntityName}\\{$localEntityName}{$relatedEntityName}Controller";
    }

    /**
     * Returns route definition name for the relation.
     * Ex. 'users/{user}/roles'
     */
    public function getRouteDefinitionPath(): string
    {
        $localEntityNamePlural = Str::snake($this->localEntity->getPluralName());
        $localEntityNameSingular = Str::snake($this->localEntity->getName());
        $relatedEntityName = Str::snake($this->getRelationName());
        return "{$localEntityNamePlural}/{{$localEntityNameSingular}}/{$relatedEntityName}";
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

    /**
     * Json representation of the relation.
     */
    public function toJson(): string
    {
        return json_encode([
            'type' => $this->type->value,
            'entity' => $this->entityName,
            'foreign_key' => $this->foreignKey,
            'local_key' => $this->localKey,
            'relation_name' => $this->relationName,
        ], JSON_PRETTY_PRINT);
    }

}
