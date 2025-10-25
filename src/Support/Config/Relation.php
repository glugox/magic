<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Builder\RelationBuilder;
use Glugox\Magic\Support\MagicNamespaces;
use Illuminate\Support\Str;
use RuntimeException;

class Relation
{
    public RelationType $type;

    private ?string $relatedEntityName;

    private string $relationName;

    private string $foreignKey;

    private string $localKey;

    private string $morphName;

    private ?string $pivotTable = null;

    public function __construct(
        RelationType|string $type, // e.g. 'hasMany', 'belongsTo', etc.
        private readonly Entity $localEntity, // the entity that owns this relation, e.g. 'User'
        ?string $relatedEntityName = null, // name of the related entity, e.g. 'Role'
        private ?Entity $relatedEntity = null, // the related entity object, e.g. Role entity
        ?string $foreignKey = null, // usually local entity's fk, e.g. user_id
        ?string $localKey = null, // usually related entity's pk, e.g. id
        private ?string $relatedKey = null, // // points to related entity in pivot table, e.g. role_id
        ?string $relationName = null,
        ?string $morphName = null, // for polymorphic relations, e.g. 'imageable'
        ?string $pivotTable = null, // for many-to-many relations, e.g. 'role_user'
        public ?bool $cascade = false
    ) {
        $this->type = $type instanceof RelationType ? $type : RelationType::from($type);
        $this->relatedEntityName = $relatedEntityName ?? $relatedEntity->name ?? null;

        $this->morphName = $morphName ?? $this->inferMorphName(); // Should come with infer before foreign key inference
        $this->relationName = $relationName ?? $this->inferRelationName();
        $this->foreignKey = $foreignKey ?? $this->inferForeignKey();
        $this->localKey = $localKey ?? $this->inferLocalKey();
        $this->pivotTable = $pivotTable ?? ($this->requiresPivotTable() ? $this->inferPivotName() : null);

        if ($this->relatedEntityName === null && $this->requiresRelatedEntityName()) {
            throw new RuntimeException('The related entity name is required.');
        }

        // relatedForeignKey is only used in BelongsToMany relations
        if ($this->type === RelationType::BELONGS_TO_MANY && ! $this->relatedKey) {
            $relatedEntityName = $this->getRelatedEntityName();
            if (! $relatedEntityName) {
                throw new RuntimeException("Related entity name is not set for BelongsToMany relation in entity {$this->getLocalEntityName()}");
            }
            $this->relatedKey = Str::snake($relatedEntityName).'_id';
        }
    }

    /**
     * Static factory method to create a Relation instance.
     */
    public static function make(RelationType $type, Entity $localEntity): RelationBuilder
    {
        return new RelationBuilder()
            ->type($type)
            ->localEntity($localEntity);
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

    public function getMorphTypeKey(): ?string
    {
        return $this->morphName ? $this->morphName.'_type' : null;
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
     *
     * @return string|null The name of the related entity, or null if not set ( null for MorphTo relations )
     */
    public function getRelatedEntityName(): ?string
    {
        return $this->relatedEntityName;
    }

    /**
     * Forcing related entity to be set
     * because we need it for various code generation tasks.
     * Throws exception if not set.
     */
    public function getRelatedEntityNameOrFail(): string
    {
        $name = $this->getRelatedEntityName();
        if (! $name) {
            throw new RuntimeException("Related entity name is not set for relation of type {$this->type->value} in entity {$this->getLocalEntityName()}");
        }

        return $name;
    }

    /**
     * Sets the related entity
     */
    public function setRelatedEntity(Entity $entity): void
    {
        $this->relatedEntity = $entity;

        // TODO: Check if foreign key exists in related entity and add it if not
        /*if ($this->type === RelationType::HAS_MANY || $this->type === RelationType::HAS_ONE) {
            $foreignKey = $this->getForeignKey();
            if ($foreignKey !== null && ! $this->relatedEntity->getFieldByName($foreignKey)) {
                // Add foreign key field to local entity
                $this->relatedEntity->addField(Field::fromConfig([
                    'name' => $foreignKey,
                    'type' => 'foreignId',
                    'nullable' => true
                ], $this->relatedEntity) );

                // Add relation to related entity
                $this->relatedEntity->addRelation(new Relation(
                    type: RelationType::BELONGS_TO,
                    localEntity: $this->relatedEntity,
                    relatedEntityName: $this->getLocalEntityName(),
                    foreignKey: $foreignKey,
                    localKey: $this->getLocalKey(),
                ));
            }
        }*/
    }

    /**
     * Get the related entity object.
     * If the related entity is not set, it throws an exception.
     *
     * Call this method only if the relation type should have a related entity. Otherwise, it will throw an exception.
     *
     * @throws RuntimeException
     */
    public function getRelatedEntity(): Entity
    {
        if (! $this->relatedEntity) {
            throw new RuntimeException("Related entity is not set for relation of type {$this->type->value}, related entity name: {$this->getRelatedEntityName()}, in entity {$this->getLocalEntityName()}");
        }

        return $this->relatedEntity;
    }

    /**
     * Returns true if the relation does have related entity.
     */
    public function hasRelatedEntity(): bool
    {
        return $this->relatedEntity !== null;
    }

    /**
     * Finds the inverse relation in the related entity, if it exists.
     * E.g. if this is a hasMany relation from User to Post,
     * it will look for a belongsTo relation from Post to User.
     * If no inverse relation is found, it returns null.
     */
    public function getInverseRelation(): ?self
    {
        if (! $this->hasRelatedEntity()) {
            return null;
        }

        $relatedEntity = $this->getRelatedEntity();
        foreach ($relatedEntity->getRelations() as $relation) {
            if ($relation->getRelatedEntityName() === $this->getLocalEntityName()) {
                return $relation;
            }
        }

        return null;
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
     * @return string Infer the name of the pivot table for many-to-many relations.
     */
    public function inferPivotName(): string
    {
        if ($this->isPolymorphic()) {
            return Str::plural($this->morphName);
        }

        // alphabetical order of related tables (Laravel convention)
        $tables = [
            Str::snake($this->getLocalEntityName()),
            Str::snake($this->getRelatedEntityNameOrFail()),
        ];
        sort($tables);

        return implode('_', $tables);
    }

    /**
     * Gets pivot table name for the relation
     */
    public function getPivotName(): string
    {
        return $this->pivotTable ?? throw new RuntimeException("Pivot table name is not set for relation of type {$this->type->value} in entity {$this->getLocalEntityName()}");
    }

    /**
     * Get the name of the related table in snake_case. Ex. 'users', 'posts', etc.
     * // It differs from relation name which is camelCase and pluralized.
     */
    public function getTableName(): string
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Returns the API path for the related entity.
     * This is usually the plural snake_case of the related entity name.
     * Ex. 'users', 'posts', etc.
     */
    public function getApiPath(): string
    {
        return Str::kebab(Str::plural($this->getRelationName()));
    }

    /**
     * Returns the web path for the related entity.
     * This is usually the plural snake_case of the related entity name.
     * Ex. 'users', 'posts', etc.
     */
    public function getWebPath(): string
    {
        return Str::kebab(Str::plural($this->getRelationName()));
    }

    /**
     * Returns the morph name for polymorphic relations.
     * Ex. 'imageable' for Image model relation to various models.
     * For non-polymorphic relations, it returns null.
     */
    public function getMorphName(): ?string
    {
        // For polymorphic relations, return the relation name
        return $this->morphName;
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
        $relatedEntityName = $this->getRelatedEntityNameOrFail();

        $controllerNamespace = MagicNamespaces::httpControllers("{$localEntityName}\\{$localEntityName}{$relatedEntityName}Controller");

        return '\\'.$controllerNamespace;
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

        if ($this->isPolymorphic()) {
            return ''; // for polymorphic relations, we do not know the related entity at this point
        }

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

    /*
     * @return bool Returns true if the relation is of type 'morphedByMany'.
     */
    public function isMorphedByMany(): bool
    {
        return $this->type === RelationType::MORPHED_BY_MANY;
    }

    /**
     * @return bool Returns true if the relation is of type 'morphToMany'.
     */
    public function isMorphToMany(): bool
    {
        return $this->type === RelationType::MORPH_TO_MANY;
    }

    /**
     * Returns true if the relation is polymorphic (morphOne, morphMany, morphTo, morphToMany, morphedByMany)
     */
    public function isPolymorphic(): bool
    {
        return in_array($this->type, [
            RelationType::MORPH_ONE,
            RelationType::MORPH_MANY,
            RelationType::MORPH_TO,
            RelationType::MORPH_TO_MANY,
            RelationType::MORPHED_BY_MANY,
        ]);
    }

    /**
     * Determines if the relation requires a pivot table.
     */
    public function requiresPivotTable(): bool
    {
        return in_array($this->type, [RelationType::BELONGS_TO_MANY, RelationType::MORPH_TO_MANY, RelationType::MORPHED_BY_MANY]);
    }

    /**
     * Determines if the relation requires a related entity name to be set.
     * MorphTo relations do not require related entity name.
     */
    public function requiresRelatedEntityName(): bool
    {
        return ! in_array($this->type, [RelationType::MORPH_TO]);
    }

    /**
     * Json representation of the relation.
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
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return $json === false ? '{}' : $json;
    }

    /**
     * String representation of the relation.
     */
    public function toString(): string
    {
        /**
         * User → hasMany(projects) → Project [ FK: user_id, LK: id ]
         */
        $relatedEntityPart = $this->hasRelatedEntity() ? $this->getRelatedEntityName() : 'N/A';
        $fkPart = $this->getForeignKey() ? "FK: {$this->getForeignKey()}" : '';
        $lkPart = $this->getLocalKey() ? "LK: {$this->getLocalKey()}" : '';
        $relatedKeyPart = $this->getRelatedKey() ? "RK: {$this->getRelatedKey()}" : '';

        return "{$this->getLocalEntityName()} → {$this->type->value}({$this->getRelationName()}) → {$relatedEntityPart} [{$fkPart}, {$lkPart}, {$relatedKeyPart}]";
    }

    /**
     * Infers the relation name if not explicitly set.
     */
    protected function inferRelationName(): string
    {
        if ($this->type === RelationType::MORPH_TO) {
            return Str::snake($this->getLocalEntityName());
        }

        if (! $this->getRelatedEntityName() && $this->requiresRelatedEntityName()) {
            throw new RuntimeException("Entity name is not set for relation of type {$this->type->value} in entity {$this->getLocalEntityName()}");
        }

        return match ($this->type) {
            RelationType::HAS_MANY,
            RelationType::BELONGS_TO_MANY,
            RelationType::MORPH_MANY => Str::plural(Str::camel($this->getRelatedEntityNameOrFail())),
            default => Str::camel($this->getRelatedEntityNameOrFail()),
        };
    }

    /**
     * Infers the foreign key if not explicitly set.
     */
    protected function inferForeignKey(): string
    {
        return match ($this->type) {
            RelationType::BELONGS_TO => Str::snake($this->getRelatedEntityNameOrFail()).'_id',
            RelationType::HAS_ONE, RelationType::HAS_MANY, RelationType::BELONGS_TO_MANY => Str::snake($this->getLocalEntityName()).'_id',
            RelationType::MORPH_TO, RelationType::MORPH_ONE, RelationType::MORPH_TO_MANY, RelationType::MORPHED_BY_MANY, RelationType::MORPH_MANY => ($this->morphName).'_id'
        };
    }

    /**
     * Infer foreign key for MorphTo relation.
     * It is usually the relation name + _id
     * Ex. for relation 'imageable', the foreign key is 'imageable_id'
     * and the type key is 'imageable_type'
     * This is only needed for MorphTo relations.
     * For other polymorphic relations, the foreign key is on the related model.
     * Ex. for morphMany relation from Post to Comment, the foreign key is on comments table.
     * So we do not need to infer it here.
     * We only need to infer it for MorphTo relations.
     *
     * @throws RuntimeException
     */
    protected function inferMorphToForeignKey(): string
    {
        return $this->morphName.'_id';
    }

    /**
     * Infers the local key if not explicitly set.
     */
    protected function inferLocalKey(): string
    {
        return 'id';
    }

    /**
     * Infers the morph name for polymorphic relations.
     * For MorphTo, it is usually the relation name.
     * For other polymorphic relations, it is usually the related entity name + 'able'.
     * Ex. for Image related to User and Post, the morph name is 'imageable'.
     */
    protected function inferMorphName(): string
    {
        return match ($this->type) {
            RelationType::MORPH_ONE, RelationType::MORPH_MANY, RelationType::MORPH_TO_MANY, RelationType::MORPHED_BY_MANY => Str::snake($this->getRelatedEntityNameOrFail()).'able',
            default => Str::snake($this->getLocalEntityName()).'able' // for MorphTo
        };
    }
}
