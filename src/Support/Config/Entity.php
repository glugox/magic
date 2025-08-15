<?php

namespace Glugox\Magic\Support\Config;

class Entity
{
    public function __construct(
        private string $name,
        /** @var Field[] */
        private array $fields,
        /** @var Relation[] */
        private array $relations = [],
        /** @var string */
        private ?string $tableName = null,
    ) {}

    /**
     * Create an Entity object from an array of properties.
     */
    public static function fromConfig(array $data): self
    {
        $fields = [];
        foreach ($data['fields'] ?? [] as $fieldData) {
            $fields[] = Field::fromConfig($fieldData);
        }

        // Create the entity with empty relations initially
        $entity = new self($data['name'], $fields, [], $data['table'] ?? null);
        $relations = [];
        foreach ($data['relations'] ?? [] as $relationData) {
            $relation = new Relation(
                $relationData['type'],
                $entity,
                $relationData['entity'] ?? null,
                $relationData['foreign_key'] ?? null,
                $relationData['local_key'] ?? null,
                $relationData['name'] ?? null
            );

            // Add the relation to the entity
            $entity->addRelation($relation);
        }

        return $entity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get db table name for the entity.
     */
    public function getTableName(): string
    {
        // Convert entity name to snake_case for table name
        return $this->tableName ??= strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getPluralName()));
    }

    /**
     * Get the foreign / primary key for the entity.
     */
    public function getForeignKey(): string
    {
        // Usually the foreign key is snake_case of the entity name + '_id'
        return \Str::snake($this->getName()).'_id';
    }

    /**
     * Get the route name for the entity.
     */
    public function getRouteName(): string
    {
        // Convert entity name to kebab-case for route name
        return $this->getTableName();
    }

    /**
     * Get folder name for the entity.
     */
    public function getFolderName(): string
    {
        return $this->getTableName();
    }

    /**
     * Get the href for the entity.
     */
    public function getHref(): string
    {
        // Convert entity name to kebab-case for href
        return '/'.$this->getTableName();
    }

    /**
     * Entity name in plural form.
     */
    public function getPluralName(): string
    {
        return \Str::plural($this->name);
    }

    /**
     * hasTimestamps
     */
    public function hasTimestamps(): bool
    {
        return true; // TODO: Implement logic to determine if timestamps are needed based on entity configuration
    }

    /**
     * Get icon for the entity.
     */
    public function getIcon(): string
    {
        return 'LayoutGrid'; // TODO: Implement logic to determine icon based on entity name or from config
    }

    /**
     * Add a field to the entity.
     */
    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * Check if the entity has a field by name.
     */
    public function hasField(string $name): bool
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get the names of fillable fields.
     *
     * @return string[]
     */
    public function getFillableFieldsNames(): array
    {
        $fillable = [];
        foreach ($this->getFillableFields() as $field) {
            $fillable[] = $field->name;
        }

        return $fillable;
    }

    /**
     * Get fillable fields.
     *
     * @return Field[]
     */
    public function getFillableFields(): array
    {
        $fillable = [];
        foreach ($this->fields as $field) {
            if (! in_array($field->name, ['id', 'created_at', 'updated_at'])) {
                $fillable[] = $field;
            }
        }

        return $fillable;
    }

    /**
     * Get the names of fields that should be hidden.
     *
     * @return Field[]
     */
    public function getHiddenFields(): array
    {
        $hidden = [];
        foreach ($this->fields as $field) {
            if (in_array($field->name, ['password', 'remember_token'])) {
                $hidden[] = $field;
            }
        }

        return $hidden;
    }

    /**
     * Get the names of hidden fields.
     *
     * @return string[]
     */
    public function getHiddenFieldsNames(): array
    {
        $hidden = [];
        foreach ($this->getHiddenFields() as $field) {
            $hidden[] = $field->name;
        }

        return $hidden;
    }

    /**
     * Get searchable fields.
     */
    public function getSearchableFields(): array
    {
        $searchable = [];
        foreach ($this->fields as $field) {
            if ($field->isSearchable()) {
                $searchable[] = $field;
            }
        }

        return $searchable;
    }

    /**
     * Get the names of searchable fields.
     *
     * @return string[]
     */
    public function getSearchableFieldsNames(): array
    {
        $searchable = [];
        foreach ($this->getSearchableFields() as $field) {
            $searchable[] = $field->getName();
        }

        return $searchable;
    }

    /**
     * Get the fields as JSON.
     */
    public function getFieldsJson(): string
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = [
                'name' => $field->name,
                'type' => $field->type->value,
                'nullable' => $field->nullable,
                'length' => $field->length,
                'precision' => $field->precision,
                'scale' => $field->scale,
                'default' => $field->default,
                'comment' => $field->comment,
            ];
        }

        return json_encode($fields, JSON_PRETTY_PRINT);
    }

    /**
     * Get the casts for the fields.
     */
    public function getCasts(): array
    {
        $casts = [];

        // TODO: Implement logic to determine casts based on field types
        return $casts;
    }

    /**
     * @return Relation[]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Add a relation to the entity.
     */
    public function addRelation(Relation $relation): void
    {
        $this->relations[] = $relation;
    }
}
