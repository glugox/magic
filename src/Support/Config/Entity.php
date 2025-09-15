<?php

namespace Glugox\Magic\Support\Config;

use Glugox\Magic\Support\Config\Entity\Settings;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

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
     * @param string|array{
     *     name: string,
     *     table?: string,
     *     fields?: list<array{
     *         name: string,
     *         type: string
     *     }>,
     *     relations?: array<string, array{
     *         type: string,
     *         model: class-string,
     *         relatedEntityName?: string,
     *         foreignKey?: string,
     *         localKey?: string,
     *         relatedKey?: string,
     *         relationName?: string
     *     }>,
     *     settings?: array{ has_images?: bool, is_searchable?: bool },
     *     icon?: string
     * } $data JSON string or associative array with entity configuration
     */
    public static function fromConfig(array|string $data): self
    {
        // Convert JSON string to array if needed
        if (is_string($data)) {
            /** @var array{
             *     name: string,
             *     table?: string,
             *     fields?: list<array{name: string, type: string}>,
             *     relations?: array<string, array{
             *         type: string,
             *         model: class-string,
             *         relatedEntityName?: string,
             *         foreignKey?: string,
             *         localKey?: string,
             *         relatedKey?: string,
             *         relationName?: string
             *     }>,
             *     settings?: array{ has_images?: bool, is_searchable?: bool },
             *     icon?: string
             * } $data
             */
            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON string provided for Entity configuration.');
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
                relatedEntityName: $relationData['relatedEntityName'] ?? null,
                foreignKey: $relationData['foreignKey'] ?? null,
                localKey: $relationData['localKey'] ?? null,
                relatedKey: $relationData['relatedKey'] ?? null,
                relationName: $relationData['relationName'] ?? null
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
        $entity->icon = $data['icon'] ?? null;

        return $entity;
    }

    /**
     * Process relations to ensure all necessary properties are set.
     */
    public function processRelations(Config $config): void
    {
        foreach (($this->relations ?? []) as $relation) {
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
                throw new RuntimeException("Related entity '{$relation->getRelatedEntityName()}' not found for relation in entity '{$this->getName()}'.");
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
     * Get the fully qualified model class name.
     * Example: "\App\Models\User", "\App\Models\Post"
     */
    public function getFullyQualifiedModelClass(): string
    {
        // Convert entity name to StudlyCase for fully qualified class name
        return '\\App\\Models\\'.$this->getClassName();
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
        return $this->tableName ??= mb_strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getPluralName()));
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
     * Get folder name for the entity.
     * Example: "users", "posts", "failed_jobs"
     */
    public function getFolderName(): string
    {
        return $this->getTableName();
    }

    /**
     * Get the href for the entity.
     * Example: "/users", "/posts"
     */
    public function getHref(): string
    {
        // Convert entity name to kebab-case for href
        return '/'.$this->getRouteName();
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
     * Adds field only if it does not exist already.
     *
     * @param  Field|array  $field  Field instance or array configuration
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
     * Add a relation to the entity.
     */
    public function addRelation(Relation $relation): void
    {
        $this->relations[] = $relation;
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
     * Get a field by name.
     */
    public function getFieldByName(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
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
        if (! $skipRelations) {
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
     * Check if the entity has a name field.
     */
    public function hasNameField(): bool
    {
        foreach (($this->fields ?? []) as $field) {
            if ($field->isMain()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  RelationType|null  $type  If provided, only relations of this type are returned.
     * @param  RelationType[]|null  $excludeTypes  If provided, relations of these types
     * @return Relation[]
     */
    public function getRelations(?RelationType $type = null, ?array $excludeTypes = null): array
    {
        if (! is_null($type)) {
            return array_filter(($this->relations ?? []), function (Relation $relation) use ($type) {
                return $relation->getType() === $type;
            });
        }

        // Exclude types if provided
        if (! is_null($excludeTypes)) {
            return array_filter(($this->relations ?? []), function (Relation $relation) use ($excludeTypes) {
                return ! in_array($relation->getType(), $excludeTypes);
            });
        }

        return $this->relations ?? [];
    }

    /**
     * Get fields that should be visible in TS objects
     *
     * @return Field[]
     */
    public function getTsFields(): array
    {
        $visible = [];

        foreach (($this->fields ?? []) as $field) {
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
     *
     * @return Field[]
     */
    public function getFormFields(): array
    {
        $visible = [];
        foreach (($this->fields ?? []) as $field) {
            // Exclude showInForm false fields
            if ($field->showInForm === false) {
                continue;
            }

            if (! in_array($field->name, ['id', 'created_at', 'updated_at', 'password', 'remember_token'])) {
                $visible[] = $field;
            }
        }
        // Relations are not needed here, as belongsTo relations are represented by foreign key fields

        return $visible;
    }

    /**
     * Get name fields as names strings.
     * Example: ['first_name', 'last_name']
     *
     * @return string[]
     */
    public function getNameFieldsNames(): array
    {
        $nameFields = [];
        foreach ($this->getNameFields() as $fieldName) {
            $nameFields[] = $fieldName->name;
        }

        return $nameFields;
    }

    /**
     * Get all name fields of the entity.
     *
     * @return Field[]
     */
    public function getNameFields(): array
    {
        $nameFields = [];
        foreach (($this->fields ?? []) as $field) {
            if ($field->isMain()) {
                $nameFields[] = $field;
            }
        }

        return $nameFields;
    }

    /**
     * Get the primary name field of the entity.
     */
    public function getPrimaryNameField(): ?Field
    {
        foreach (($this->fields ?? []) as $field) {
            if ($field->isMain()) {
                return $field;
            }
        }

        return null;
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

    /*
     * Get setting vale for a specific key.
     */

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
     * Get relations without morph relations.
     */
    public function getRelationsWithoutMorph(): array
    {
        return array_filter($this->relations, function (Relation $relation) {
            return ! in_array($relation->getType(), [
                RelationType::MORPH_TO,
                RelationType::MORPH_MANY,
                RelationType::MORPH_ONE,
                RelationType::MORPH_TO_MANY,
                RelationType::MORPHED_BY_MANY,
            ]);
        });
    }

    /**
     * Get relations that have a valid related entity defined.
     *
     * @return Relation[]
     */
    public function getRelationsWithValidEntity(): array
    {
        return array_filter($this->relations, function (Relation $relation) {
            return ! empty($relation->getRelatedEntityName());
        });
    }

    /**
     * Get the relation by name.
     */
    public function getRelationByName(string $name): ?Relation
    {
        return array_find($this->relations ?? [], fn ($relation) => $relation->getRelationName() === $name);
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
     * Get the foreign / primary key for the entity.
     */
    public function getForeignKey(): string
    {
        // Usually the foreign key is snake_case of the entity name + '_id'
        return Str::snake($this->getName()).'_id';
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

    /**
     * Whether the entity has images
     * defined in settings json config.
     */
    public function hasImages()
    {
        return $this->settings->hasImages;
    }

    /**
     * Assign priority values to fields for ordering.
     */
    protected function fieldPriority(Field $field): int
    {
        if ($field->name === 'id') {
            return 0;
        }

        if ($field->isMain()) {
            return 1;
        }

        if ($field->isBelongsTo()) {
            return 2;
        }

        // everything else after
        return 3;
    }

    /**
     * Ensure there is at least one main field defined.
     * If not, set the first string field as main.
     *
     * @return void
     */
    public function ensureMainField(): void
    {
        if ($this->hasNameField()) {
            return;
        }

        // Try to find first string field
        foreach (($this->fields ?? []) as $field) {
            if ( in_array($field->type, [FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::EMAIL], true)) {
                $field->asMain();
                return;
            }
        }

        // If no string field, set the first field as main
        if (! empty($this->fields)) {
            $this->fields[0]->asMain();
        }
    }
}
