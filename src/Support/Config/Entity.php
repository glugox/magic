<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Entity\Settings;
use Illuminate\Support\Str;

class Entity
{
    public function __construct(
        // Entity name, e.g. "User", "Post"
        private string $name,
        /** @var Field[] */
        private array $fields,
        /** @var Relation[] */
        private array $relations = [],
        /** @var string */
        private ?string $tableName = null,
        // settings for the entity, e.g. timestamps, soft deletes, etc.
        private ?Settings $settings = new Settings([]),
        // Icon
        private ?string $icon = null,
    ) {}

    /**
     * Create an Entity object from an array of properties.
     */
    public static function fromConfig(array $data): self
    {
        // Create the entity with empty relations initially
        $entity = new self($data['name'], [], [], $data['table'] ?? null);

        $fields = [];
        foreach ($data['fields'] ?? [] as $fieldData) {
            $field = Field::fromConfig($fieldData, $entity);
            $entity->addField($field);
        }

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

        // Set the entity's settings if provided
        if (isset($data['settings'])) {
            $settings = new Settings($data['settings']);
            $entity->settings = $settings;
        }

        // Set the entity's icon if provided
        if (isset($data['icon'])) {
            $entity->icon = $data['icon'] ?? null;
        }

        return $entity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the entity's short class name.
     * Example: "User", "Post"
     */
    public function getClassName(): string
    {
        // Convert entity name to StudlyCase for class name
        return $this->name;
    }

    /**
     * Get the fully qualified model class name.
     * Example: "\App\Models\User", "\App\Models\Post"
     */
    public function getFullyQualifiedModelClass(): string
    {
        // Convert entity name to StudlyCase for fully qualified class name
        return '\\App\\Models\\'.$this->getClassName();
    }

    /**
     * Get directory name for the entity.
     */
    public function getDirectoryName(): string
    {
        return $this->getTableName();
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
        return Str::snake($this->getName()).'_id';
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
     * Get the resource path for the entity.
     */
    public function getResourcePath(): string
    {
        // Convert entity name to kebab-case for resource path
        return $this->getFolderName().'.index';
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
        return Str::plural($this->name);
    }

    /**
     * Entity name in singular form.
     */
    public function getSingularName(): string
    {
        return Str::singular($this->name);
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
        return $this->icon ?? 'LayoutGrid';
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
     * Get fields that should be visible in tables/lists.
     *
     * @return Field[]
     */
    public function getTableFields(): array
    {
        $visible = [];

        foreach ($this->fields as $field) {

            // Exclude showInTable false fields
            if ($field->showInTable === false) {
                continue;
            }

            if (! in_array($field->name, ['password', 'remember_token'])) {
                $visible[] = $field;
            }
        }

        // Ensure we have name field in the list
        if (! $this->hasNameField()) {
            $nameField = new Field('name', FieldType::STRING);
            $visible[] = $nameField;
        }

        // Relations
        foreach ($this->getRelations() as $relation) {
            $relationField = Field::fromRelation($relation);
            $visible[] = $relationField;
        }

        // Reorder: id first, name second, BELONGS_TO fields next
        usort($visible, function ($a, $b) {
            return $this->fieldPriority($a) <=> $this->fieldPriority($b);
        });

        return $visible;
    }

    /**
     * Check if the entity has a name field.
     */
    public function hasNameField(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->isName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign priority values to fields for ordering.
     */
    protected function fieldPriority(Field $field): int
    {
        if ($field->name === 'id') {
            return 0;
        }

        if ($field->isName()) {
            return 1;
        }

        if ($field->isBelongsTo()) {
            return 2;
        }

        // everything else after
        return 3;
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
            if ($field->searchable) {
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

    /*
     * Get setting vale for a specific key.
     */
    public function getSetting(string $key): mixed
    {
        return $this->settings->get($key);
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
    public function getRelations(?RelationType $type = null): array
    {
        if (! is_null($type)) {
            return array_filter($this->relations, function (Relation $relation) use ($type) {
                return $relation->getType() === $type;
            });
        }

        return $this->relations;
    }

    /**
     * Add a relation to the entity.
     */
    public function addRelation(Relation $relation): void
    {
        $this->relations[] = $relation;
    }

    /**
     * Get the relation by name.
     */
    public function getRelationByName(string $name): ?Relation
    {
        foreach ($this->relations as $relation) {
            if ($relation->getTableName() === $name) {
                return $relation;
            }
        }

        return null;
    }

    /**
     * Get the relation by entity field.
     */
    public function getRelationByField(Field $field): ?Relation
    {
        foreach ($this->relations as $relation) {
            if ($relation->getForeignKey() === $field->name) {
                return $relation;
            }
        }

        return null;
    }
}
