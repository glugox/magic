<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Entity\Settings;
use Illuminate\Support\Str;

class Entity
{
    public function __construct(
        // Entity name, e.g. "User", "Post"
        public string $name,
        /** @var Field[] */
        public ?array $fields = [],
        /** @var Relation[] */
        public ?array $relations = [],
        /** @var string */
        public ?string $tableName = null,
        // settings for the entity, e.g. timestamps, soft deletes, etc.
        public ?Settings $settings = new Settings([]),
        // Icon
        public ?string $icon = null,
    ) {}

    /**
     * Create an Entity object from an array of properties.
     */
    public static function fromConfig(array|string $data): self
    {
        // Convert JSON string to array if needed
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON string provided for Entity configuration.');
            }
        }

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
                null, // related entity will be set later in processRelations
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

    /**
     * Process relations to ensure all necessary properties are set.
     */
    public function processRelations(Config $config): void
    {
        foreach ($this->relations as $relation) {
            // If related entity is not set, skip processing
            if (empty($relation->getRelatedEntityName())) {
                continue;
            }

            // Find the related entity in the config
            $relatedEntity = null;
            foreach ($config->entities as $entity) {
                if ($entity->getName() === $relation->getRelatedEntityName()) {
                    $relatedEntity = $entity;
                    $relation->setRelatedEntity($relatedEntity);
                    break;
                }
            }

            if (! $relatedEntity) {
                throw new \RuntimeException("Related entity '{$relation->getRelatedEntityName()}' not found for relation in entity '{$this->getName()}'.");
            }
        }
    }

    /**
     * Get the entity's name.
     * Example: "User", "Post"
     */
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
     * Example: "users", "posts", "failed_jobs"
     */
    public function getDirectoryName(): string
    {
        return $this->getTableName();
    }

    /**
     * Get db table name for the entity.
     * Example: "users", "posts"
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
     * Example: "users", "posts"
     */
    public function getRouteName(): string
    {
        // Convert entity name to kebab-case for route name
        return Str::kebab($this->getPluralName());
    }

    /**
     * Get folder name for the entity.
     * Example: "users", "posts", "failed_jobs"
     */
    public function getFolderName(): string
    {
        return $this->getTableName();
    }

    /**
     * Get the resource path for the entity.
     * Example: "users.index", "posts.index"
     * This is definition that can be passed to route() helper to generate URLs.
     */
    public function getIndexRouteName(): string
    {
        // Convert entity name to kebab-case for resource path
        return $this->getFolderName().'.index';
    }

    /**
     * Get the href for the entity.
     * Example: "/users", "/posts"
     */
    public function getHref(): string
    {
        // Convert entity name to kebab-case for href
        return '/' . $this->getRouteName();
    }

    /**
     * Entity name in plural form.
     * Ex. "Users", "Posts"
     */
    public function getPluralName(): string
    {
        return Str::plural($this->name);
    }

    /**
     * Entity name in singular form.
     * Ex. "User", "Post"
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
    public function addField(Field|array $field): void
    {
        if (is_array($field)) {
            $field = Field::fromConfig($field, $this);
        }
        $this->fields[] = $field;
    }

    /**
     * Adds field only if it does not exist already.
     */
    public function addFieldIfNotExists(Field|array $field): void
    {
        if (is_array($field)) {
            $field = Field::fromConfig($field, $this);
        }
        if (! $this->hasField($field->name)) {
            $this->fields[] = $field;
        }
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
    public function getTableFields(?bool $skipRelations = false): array
    {
        $visible = [];

        foreach ($this->fields as $field) {

            // Exclude showInTable false fields
            if ($field->showInTable === false) {
                continue;
            }

            if (! in_array($field->name, ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at'])) {
                $visible[] = $field;
            }
        }

        // Ensure we have name field in the list
        if (! $this->hasNameField()) {
            $nameField = new Field('name', FieldType::STRING);
            $visible[] = $nameField;
        }

        // Relations
        if (!$skipRelations) {
            foreach ($this->getRelations() as $relation) {

                // Only BELONGS_TO relations are shown in tables
                if ($relation->getType() !== RelationType::BELONGS_TO) {
                    continue;
                }

                $relationField = Field::fromRelation($relation);
                $visible[] = $relationField;
            }
        }

        // Reorder: id first, name second, BELONGS_TO fields next
        usort($visible, function ($a, $b) {
            return $this->fieldPriority($a) <=> $this->fieldPriority($b);
        });

        return $visible;
    }

    /**
     * Get names of fields that should be visible in tables/lists.
     */
    public function getTableFieldsNames(?bool $skipRelations = false): array
    {
        $visible = [];
        foreach ($this->getTableFields($skipRelations) as $field) {
            $visible[] = $field->name;
        }
        return $visible;
    }

    /**
     * Get fields that should be visible in TS objects
     *
     * @return Field[]
     */
    public function getTsFields(): array
    {
        $visible = [];

        foreach ($this->fields as $field) {
            if (! in_array($field->name, ['password', 'remember_token'])) {
                $visible[] = $field;
            }
        }

        // Ensure we have name field in the list
        if (! $this->hasNameField()) {
            $nameField = new Field('name', FieldType::STRING);
            $visible[] = $nameField;
        }

        // Reorder: id first, name second, BELONGS_TO fields next
        usort($visible, function ($a, $b) {
            return $this->fieldPriority($a) <=> $this->fieldPriority($b);
        });

        return $visible;
    }

    /**
     * Get the names of fields that should be visible in forms.
     */
    public function getFormFields(): array
    {
        $visible = [];
        foreach ($this->fields as $field) {
            // Exclude showInForm false fields
            if ($field->showInForm === false) {
                continue;
            }

            if (! in_array($field->name, ['id', 'created_at', 'updated_at', 'password', 'remember_token'])) {
                $visible[] = $field;
            }
        }
        // Relations
        foreach ($this->getRelations() as $relation) {

            // Add only BELONGS_TO relations to forms
            if ($relation->getType() !== RelationType::BELONGS_TO
                || $relation->getType() !== RelationType::HAS_ONE) {
                continue;
            }

            $relationField = Field::fromRelation($relation);
            $visible[] = $relationField;
        }

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
     * Get all name fields of the entity.
     */
    public function getNameFields(): array
    {
        $nameFields = [];
        foreach ($this->fields as $field) {
            if ($field->isName()) {
                $nameFields[] = $field;
            }
        }

        return $nameFields;
    }

    /**
     * Get name fields as names strings.
     */
    public function getNameFieldsNames(): array
    {
        $nameFields = [];
        foreach ($this->getNameFields() as $field) {
            $nameFields[] = $field->name;
        }

        return $nameFields;
    }

    /**
     * Get the primary name field of the entity.
     */
    public function getPrimaryNameField(): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->isName()) {
                return $field;
            }
        }

        return null;
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
                'sometimes' => $field->sometimes,
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
     * Get relations that have a valid related entity defined.
     * @return Relation[]
     */
    public function getRelationsWithValidEntity(): array
    {
        return array_filter($this->relations, function (Relation $relation) {
            return ! empty($relation->getRelatedEntityName());
        });
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

    /**
     * Returns json representation of the entity.
     */
    public function toJson(): string
    {
        $data = [
            'name' => $this->name,
            'table' => $this->getTableName(),
            'icon' => $this->icon,
            'fields' => array_map(fn ($field) => json_decode($field->toJson(), true), $this->fields),
            'relations' => array_map(fn ($relation) => json_decode($relation->toJson(), true), $this->relations),
            'settings' => json_decode($this->settings->toJson()),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
