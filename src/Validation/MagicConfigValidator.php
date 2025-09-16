<?php

namespace Glugox\Magic\Validation;

use Glugox\Magic\Support\Config\Config;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use InvalidArgumentException;

class MagicConfigValidator
{
    /**
     * Constructor
     */
    public function __construct(
        protected Config $config,
    ) {}

    /**
     * Validate config
     *
     * We need to validate:
     * - Each entity has a name
     * - Each entity has at least one field
     * - Each field has a name and type
     * - Each relation has a type and target entity
     * - Each relation has a foreign key if required
     * - Each relation's target entity exists
     * - Each relation's foreign key exists in the appropriate entity
     *   - For HasOne and HasMany, the foreign key should exist in the related entity
     *     - In the related entity, for that foreign key, we should have a BelongsTo relation back,
     *       If it does not exist, we create it automatically, so the frontend can use it to show related object as BelongsTo
     *  - For BelongsTo, the foreign key should exist in the local entity
     *    - In the related entity, for that foreign key, we should have a HasOne or HasMany relation back,
     *      but throw an error if not, fo not create it automatically like for HasOne and HasMany
     *      The error would be "BelongsTo relation from Entity A to Entity B requires a HasOne or HasMany relation back from Entity B to Entity A."
     *
     * - Each pivot table for many-to-many relations is defined
     * - Each morph relation has the necessary fields
     * - No circular dependencies in relations (if applicable)
     *
     * @throws InvalidArgumentException if validation fails
     */
    public function validate(Config $config): void
    {
        $this->config = $config;
        foreach ($this->config->entities as $entity) {
            $this->validateEntity($entity);
        }
    }

    /**
     * Validate entity
     */
    protected function validateEntity(Entity $entity): void
    {
        // Validate entity name
        if (empty($entity->name)) {
            throw new InvalidArgumentException('Entity name is required');
        }

        // Validate fields
        if (empty($entity->fields)) {
            throw new InvalidArgumentException("Entity {$entity->name} must have at least one field");
        }

        foreach ($entity->fields as $field) {
            // Validate field name
            if (empty($field->name)) {
                throw new InvalidArgumentException("Field name is required in entity {$entity->name}");
            }

            // Validate field type
            if (empty($field->type)) {
                throw new InvalidArgumentException("Field type is required for field {$field->name} in entity {$entity->name}");
            }
        }

        // Validate relations
        if (! empty($entity->relations)) {
            foreach ($entity->relations as $relation) {
                // Validate relation type
                if (empty($relation->type)) {
                    throw new InvalidArgumentException("Relation type is required in entity {$entity->name}");
                }

                // Validate target entity
                if (! $relation->hasRelatedEntity() && $relation->requiresRelatedEntityName()) {
                    throw new InvalidArgumentException("Target entity is required for relation in entity {$entity->name}");
                }

                // Validate Relation
                $this->validateRelation($entity, $relation);
            }
        }
    }

    /**
     * Validate relation
     */
    private function validateRelation(Entity $entity, Relation $relation): void
    {

        $relationInfo = $relation->toString();

        if ($relation->requiresRelatedEntityName()) {
            // Check if related entity exists
            $relatedEntityName = $relation->getRelatedEntityName();
            if (empty($relatedEntityName)) {
                throw new InvalidArgumentException("Related entity name is required for relation in entity {$entity->name}");
            }

            $relatedEntity = $this->config->getEntityByName($relatedEntityName);
            if (! $relatedEntity) {
                throw new InvalidArgumentException("Related entity {$relation->getRelatedEntityName()} not found for relation in entity {$entity->name}");
            }
        }

        // Foreign key
        $foreignKey = $relation->getForeignKey();

        // Additional relation validations can be added here
        switch ($relation->getType()) {
            case RelationType::BELONGS_TO:
                // HasOne and HasMany require foreign key on local entity
                if (empty($foreignKey)) {
                    throw new InvalidArgumentException("Foreign key is required for relation {$relationInfo} in entity {$entity->name}");
                }
                $entity->ensureForeignKey($foreignKey, $entity);
                break;

            case RelationType::HAS_ONE:
            case RelationType::HAS_MANY:
                // HasOne and HasMany require foreign key on related entity
                if (empty($foreignKey)) {
                    throw new InvalidArgumentException("Foreign key is required for relation {$relationInfo} in entity {$entity->name}");
                }
                $relatedEntity->ensureForeignKey($foreignKey, $entity);
                break;

            case RelationType::BELONGS_TO_MANY:

                $pivotTable = $relation->getPivotName();
                if (empty($pivotTable)) {
                    throw new InvalidArgumentException("Pivot table name is required for BELONGS_TO_MANY relation {$relationInfo} in entity {$entity->name}");
                }

                // TODO: Make configurable
                // Ensure reverse relation exists
                $relatedEntity->ensureRelationByPivotTable($pivotTable);

                break;

            case RelationType::MORPH_TO:
                // assertFieldExists($entity, $relation['name'].'_id');
                // assertFieldExists($entity, $relation['name'].'_type');
                $ok = 1;
                break;

            case RelationType::MORPH_ONE:
            case RelationType::MORPH_MANY:
                // assertFieldExists($relatedEntityName, $relation['name'].'_id');
                // assertFieldExists($relatedEntityName, $relation['name'].'_type');
                $ok = 1;
                break;

            case RelationType::MORPH_TO_MANY:
                /*assertPivotExists($relation['pivot'], [
                    $relation['morphableType'].'_id',
                    $relation['morphableType'].'_type',
                    $relation['foreignKey']
                ]);*/
                $ok = 1;
                break;
        }
    }
}
