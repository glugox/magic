<?php

namespace Glugox\Magic\Support\Config;

use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class Relation
{
    public RelationType $type;

    public function __construct(
        RelationType|string $type, // e.g. 'hasMany', 'belongsTo', etc.
        private readonly Entity $localEntity, // the entity that owns this relation, e.g. 'User'
        private readonly ?string $relatedEntityName = null, // name of the related entity, e.g. 'Role'
        private ?Entity $relatedEntity = null, // the related entity object, e.g. Role entity
        private ?string $foreignKey = null, // usually local entity's fk, e.g. user_id
        private ?string $localKey = null, // usually related entity's pk, e.g. id
        private ?string $relatedKey = null, // // points to related entity in pivot table, e.g. role_id
        private ?string $relationName = null,
    ) {
        $this->type = $type instanceof RelationType ? $type : RelationType::from($type);

        $this->relationName ??= $this->inferRelationName();
        $this->foreignKey ??= $this->inferForeignKey();
        $this->localKey ??= $this->inferLocalKey();
        // relatedForeignKey is only used in BelongsToMany relations
        if ($this->type === RelationType::BELONGS_TO_MANY && ! $this->relatedKey) {
            $this->relatedKey = Str::snake($this->getRelatedEntityName()).'_id';
        }
    }

    /**
     * The related entity's ty
     */
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

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): ?string
    {
        return $this->localKey;
    }

    public function getRelatedKey(): ?string
    {
        return $this->relatedKey;
    }

    /**
     * Gets local entity name
     */
    public function getLocalEntityName(): string
    {
        return $this->localEntity->getName();
    }

    /**
     * The related entity name, or null if not set
     * This can not be inferred from $this->relatedEntity
     * because the related entity may not be set yet. We actually
     * need the name to find the related entity in the config.
     */
    public function getRelatedEntityName(): ?string
    {
        return $this->relatedEntityName;
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
     * @throws RuntimeException
     */
    public function getRelatedEntity(): Entity
    {
        if (! $this->relatedEntity) {
            throw new RuntimeException("Related entity is not set for relation of type {$this->type->value}");
        }

        return $this->relatedEntity;
    }

    /**
     * Has route
     */
    public function hasRoute(): bool
    {
        return in_array($this->type, [
            RelationType::HAS_MANY,
            RelationType::BELONGS_TO_MANY,
            RelationType::MORPH_MANY
        ]);
    }

    /**
     * @return string Returns the name of the pivot table for many-to-many relations.
     */
    public function getPivotName(): string
    {
        // alphabetical order of related tables (Laravel convention)
        $tables = [
            Str::snake($this->getLocalEntityName()),
            Str::snake($this->getRelatedEntityName()),
        ];
        sort($tables);

        return implode('_', $tables);
    }

    /**
     * Get the name of the related table in snake_case. Ex. 'users', 'posts', etc.
     * // It differs from relation name which is camelCase and pluralized.
     */
    public function getTableName(): string
    {

        // If entity name is not set, return empty string
        if (! $this->getRelatedEntityName()) {
            // If relation is MorphTo, return local entity's table name
            if ($this->type === RelationType::MORPH_TO) {
                return $this->getLocalEntity()->getTableName();
            }

            throw new RuntimeException("Entity name is not set for relation of type {$this->type->value}");
        }

        // Convert entity name to snake_case for table name
        return Str::snake(Str::plural($this->getRelatedEntityName()));
    }

    /**
     * Returns the API path for the related entity.
     * This is usually the plural snake_case of the related entity name.
     * Ex. 'users', 'posts', etc.
     */
    public function getApiPath(): string
    {
        return Str::kebab(Str::plural($this->getRelatedEntityName()));
    }

    /**
     * Returns the morph name for polymorphic relations.
     * Ex. 'imageable' for Image model relation to various models.
     * For non-polymorphic relations, it returns null.
     */
    public function getMorphName(): ?string
    {
        // For polymorphic relations, return the relation name
        return $this->getRelationName();
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
        $relatedEntityName = $this->getRelatedEntityName();

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
     * Returns a comma-separated string of fields to be eagerly loaded for the related entity.
     * This is usually "id,name" or "id,title" depending on the related entity's name field.
     * s
     */
    public function getEagerFieldsStr(): string
    {
        // If the entity does not have a 'name' field, we will try to find first field that can be used as name
        // so we can load them in index listing
        $eagerFieldNames = ['id'];
        $nameFields = $this->getRelatedEntity()->getNameFieldsNames();
        if (count($nameFields) > 0) {
            $eagerFieldNames[] = $nameFields[0];
        }

        return implode(',', array_filter($eagerFieldNames));
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
     * Returns true if the relation is of type 'BelongsToMany'.
     */
    public function isBelongsToMany(): bool
    {
        return $this->type === RelationType::BELONGS_TO_MANY;
    }

    /**
     * @return bool Returns true if the relation is of type 'morphTo'.
     */
    public function isMorphTo(): bool
    {
        return $this->type === RelationType::MORPH_TO;
    }

    /**
     * @return bool Returns true if the relation is of type 'morphMany'.
     */
    public function isMorphMany(): bool
    {
        return $this->type === RelationType::MORPH_MANY;
    }

    /**
     * @return bool Returns true if the relation is of type 'morphOne'.
     */
    public function isMorphOne(): bool
    {
        return $this->type === RelationType::MORPH_ONE;
    }

    /**
     * Json representation of the relation.
     *
     * @throws JsonException
     */
    public function toJson(): string
    {
        $data = [
            'type' => $this->type->value,
            'relatedEntityName' => $this->getRelatedEntityName(),
            'relationName' => $this->getRelationName(),
            'foreignKey' => $this->getForeignKey(),
            'localKey' => $this->getLocalKey(),
            'relatedForeignKey' => $this->getRelatedKey(),
            'localEntityName' => $this->getLocalEntityName(),

        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    protected function inferRelationName(): string
    {
        if ($this->relationName) {
            return $this->relationName;
        }

        if (! $this->getRelatedEntityName()) {
            if ($this->type === RelationType::MORPH_TO) {
                throw new RuntimeException('MorphTo requires explicit relation name');
            }
            throw new RuntimeException("Entity name is not set for relation of type {$this->type->value} in entity {$this->getLocalEntityName()}");
        }

        return match ($this->type) {
            RelationType::HAS_MANY,
            RelationType::BELONGS_TO_MANY,
            RelationType::MORPH_MANY => Str::plural(Str::camel($this->getRelatedEntityName())),
            default => Str::camel($this->getRelatedEntityName()),
        };
    }

    protected function inferForeignKey(): ?string
    {
        if ($this->foreignKey) {
            return $this->foreignKey;
        }

        return match ($this->type) {
            RelationType::BELONGS_TO => Str::snake($this->getRelatedEntityName()).'_id',
            RelationType::HAS_ONE,
            RelationType::HAS_MANY => Str::snake($this->getLocalEntityName()).'_id',
            RelationType::MORPH_TO => null, // handled by *_id + *_type in fields
            default => null, // belongsToMany & morphMany usually need explicit
        };
    }

    protected function inferLocalKey(): ?string
    {
        if ($this->localKey) {
            return $this->localKey;
        }

        return Str::snake($this->getRelatedEntityName()).'_id';
    }
}
