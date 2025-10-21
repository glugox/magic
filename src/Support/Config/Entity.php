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
        /** @var Filter[] */
        public ?array $filters = [],
        /** @var Action[] */
        public ?array $actions = [],
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
     *         relationName?: string,
     *         cascade?: bool
     *     }>,
     *     filters?: array<string, array{
     *         type: string,
     *         field: string,
     *         initialValues?: array{string, mixed},
     *         dynamic?: boolean
     *     }>,
     *     settings?: array{ has_images?: bool, is_searchable?: bool },
     *     icon?: string,
     *     actions?: list<array{
     *         name: string,
     *         type?: string,
     *         command?: string,
     *         field?: string,
     *         label?: string,
     *         icon?: string,
     *         description?: string
     *     }>
     * } $data JSON string or associative array with entity configuration
     */
    public static function fromConfig(array|string $data): self
    {
        // Convert JSON string to array if needed
        if (is_string($data)) {
            /** @var string $data */
            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON string provided for Entity configuration.');
            }
        }

        // Create the entity with empty relations initially
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
         *         relationName?: string,
         *         cascade?: bool
         *     }>,
         *     filters?: array<string, array{
         *         type: string,
         *         field: string,
         *         initialValues?: array<string, mixed>,
         *         dynamic?: boolean
         *     }>,
         *     settings?: array{ has_images?: bool, is_searchable?: bool },
         *     icon?: string
         * } $data
         */
        $entity = new self($data['name'], [], [], [], [], $data['table'] ?? null);

        foreach ($data['fields'] ?? [] as $fieldData) {
            $field = Field::fromConfig($fieldData, $entity);
            $entity->addField($field);
        }

        foreach ($data['actions'] ?? [] as $actionData) {
            $entity->addAction($actionData);
        }

        foreach ($data['relations'] ?? [] as $relationData) {
            $relation = new Relation(
                $relationData['type'],
                $entity,
                relatedEntityName: $relationData['relatedEntityName'] ?? null,
                foreignKey: $relationData['foreignKey'] ?? null,
                localKey: $relationData['localKey'] ?? null,
                relatedKey: $relationData['relatedKey'] ?? null,
                relationName: $relationData['relationName'] ?? null,
                cascade: $relationData['cascade'] ?? false
            );

            // Add the relation to the entity
            $entity->addRelation($relation);
        }

        // Filters
        if (isset($data['filters'])) {
            foreach ($data['filters'] as $filterData) {
                $filter = new Filter(
                    $filterData['type'],
                    $filterData['field'],
                    $filterData['initialValues'] ?? [],
                    $filterData['dynamic'] ?? false,
                    $filterData['label'] ?? null,
                    $filterData['relatedEntityName'] ?? null
                );
                $entity->filters[] = $filter;
            }
        }

        // Set the entity's settings if provided
        if (isset($data['settings'])) {
            $settings = new Settings($data['settings']);
            $entity->settings = $settings;
        }

        // Set the entity's icon if provided
        $entity->icon = $data['icon'] ?? null;

        // Process Entity after creation
        $entity->afterCreated();

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

    public function getMainFieldName()
    {
        $mainField = $this->getPrimaryNameField();

        return $mainField ? $mainField->name : 'name';
    }

    /**
     * Get the fully qualified model class name.
     * Example: "\App\Models\User", "\App\Models\Post"
     */
    public function getFullyQualifiedModelClass(): string
    {
        // Convert entity name to StudlyCase for fully qualified class name
        return 'App\\Models\\'.$this->getClassName();
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
        return str_replace('_', '-', $this->getTableName());
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
    public function getBaseRoute(): string
    {
        // Convert entity name to kebab-case for resource path
        return $this->getRouteName();
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
     * @param  Field|array{
     *      name: string,
     *      type: string,
     *      required?: bool,
     *      nullable?: bool,
     *      sometimes?: bool,
     *      length?: int|null,
     *      precision?: int|null,
     *      scale?: int|null,
     *      default?: mixed,
     *      comment?: string|null,
     *      sortable?: bool,
     *      searchable?: bool,
     *      main?: bool,
     *      showInTable?: bool,
     *      showInForm?: bool,
     *      values?: string[],
     *      min?: int,
     *      max?: int
     *  }  $field  Field instance or array configuration
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
     *
     * @param  Field|array{
     *      name: string,
     *      type: string,
     *      required?: bool,
     *      nullable?: bool,
     *      sometimes?: bool,
     *      length?: int|null,
     *      precision?: int|null,
     *      scale?: int|null,
     *      default?: mixed,
     *      comment?: string|null,
     *      sortable?: bool,
     *      searchable?: bool,
     *      main?: bool,
     *      showInTable?: bool,
     *      showInForm?: bool,
     *      values?: string[],
     *      min?: int,
     *      max?: int
     *  } $field  Field instance or array configuration
     */
    public function addField(Field|array $field): void
    {
        if (is_array($field)) {
            $field = Field::fromConfig($field, $this);
        }
        $this->fields[] = $field;
    }

    /**
     * Adds field at position, after passed types
     *
     * @param Field|array{
     *       name: string,
     *       type: string,
     *       required?: bool,
     *       nullable?: bool,
     *       sometimes?: bool,
     *       length?: int|null,
     *       precision?: int|null,
     *       scale?: int|null,
     *       default?: mixed,
     *       comment?: string|null,
     *       sortable?: bool,
     *       searchable?: bool,
     *       main?: bool,
     *       showInTable?: bool,
     *       showInForm?: bool,
     *       values?: string[],
     *       min?: int,
     *       max?: int
     *   } $field Field instance or array configuration
     * @param  FieldType[]  $afterTypes
     */
    public function addFieldAfterTypes(Field|array $field, array $afterTypes): void
    {
        if (is_array($field)) {
            $field = Field::fromConfig($field, $this);
        }

        // Default: append at the end
        $insertPos = count($this->fields ?? []);

        if (! empty($this->fields)) {
            foreach (array_reverse($this->fields, true) as $index => $existingField) {
                if (in_array($existingField->type, $afterTypes, true)) {
                    $insertPos = $index + 1;
                    break;
                }
            }
        }

        // Insert at the correct position
        if (empty($this->fields)) {
            $this->fields = [];
        }
        array_splice($this->fields, $insertPos, 0, [$field]);
    }

    /**
     * Add a relation to the entity.
     */
    public function addRelation(Relation $relation): void
    {
        $this->relations[] = $relation;
    }

    /**
     * Add action definition to the entity.
     */
    public function addAction(Action|array $action): void
    {
        if (is_array($action)) {
            $action = Action::fromConfig($action, $this);
        }

        $this->actions[] = $action;
    }

    /**
     * Add a filter to the entity.
     */
    public function addFilter(Filter $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Get relations that hasRoute
     *
     * @return Relation[]
     */
    public function getRelationsWithRoute(): array
    {
        return array_filter(($this->relations ?? []), fn ($relation) => $relation->hasRoute());
    }

    /**
     * Check if the entity has a field by name.
     */
    public function hasField(string $name): bool
    {
        return array_any(($this->fields ?? []), fn ($field) => $field->name === $name);
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields ?? [];
    }

    /**
     * Get a field by name.
     */
    public function getFieldByName(string $name): ?Field
    {
        return array_find(($this->fields ?? []), fn ($field) => $field->name === $name);
    }

    /**
     * Get names of fields that should be visible in tables/lists.
     *
     * @return string[]
     */
    public function getTableFieldsNames(): array
    {
        $visible = [];
        foreach ($this->getTableFields() as $field) {
            $visible[] = $field->name;
        }

        return $visible;
    }

    /**
     * Get fields that should be visible in tables/lists.
     *
     * @return Field[]
     */
    public function getTableFields(): array
    {
        $visible = [];
        foreach (($this->fields ?? []) as $field) {
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
        // if (! $skipRelations) {
        foreach ($this->getRelations() as $relation) {

            // Only BELONGS_TO relations are shown in tables
            if ($relation->getType() !== RelationType::BELONGS_TO) {
                continue;
            }

            $relationField = Field::fromRelation($relation);
            $visible[] = $relationField;
        }
        // }

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
        return array_any(($this->fields ?? []), fn ($field) => $field->isMain());
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
     * Get filters that should be visible in lists.
     *
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters ?? [];
    }

    /**
     * Get configured actions for the entity.
     *
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions ?? [];
    }

    public function hasActions(): bool
    {
        return ! empty($this->actions);
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

            if (! in_array($field->name, ['created_at', 'updated_at', 'remember_token'])) {
                $visible[] = $field;
            }
        }
        // Relations are not needed here, as belongsTo relations are represented by foreign key fields

        // Reorder: id first, name second, BELONGS_TO fields next
        usort($visible, function ($a, $b) {
            return $this->fieldPriority($a) <=> $this->fieldPriority($b);
        });

        return $visible;
    }

    /**
     * Get names of fields that should be visible in forms.
     */
    public function getFormFieldsNames(): array
    {
        return array_map(fn (Field $field) => $field->name, $this->getFormFields());
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
     * Tries: main field, then "name", then "title", then first string field, else null.
     */
    public function getPrimaryNameField(): ?Field
    {
        // 1. Main field
        $main = array_find(($this->fields ?? []), fn ($field) => $field->isMain());
        if ($main) {
            return $main;
        }

        // 2. Field named "name"
        $nameField = $this->getFieldByName('name');
        if ($nameField) {
            return $nameField;
        }

        // 3. Field named "title"
        $titleField = $this->getFieldByName('title');
        if ($titleField) {
            return $titleField;
        }

        // 4. First string-like field
        foreach (($this->fields ?? []) as $field) {
            if (in_array($field->type, [FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::EMAIL], true)) {
                return $field;
            }
        }

        // 5. No suitable field found
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

    /**
     * Get fillable fields.
     *
     * @return Field[]
     */
    public function getFillableFields(): array
    {
        $fillable = [];
        foreach (($this->fields ?? []) as $field) {
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
        foreach (($this->fields ?? []) as $field) {
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
            $searchable[] = $field->name;
        }

        return $searchable;
    }

    /**
     * Get searchable fields.
     *
     * @return Field[]
     */
    public function getSearchableFields(): array
    {
        $searchable = [];
        foreach (($this->fields ?? []) as $field) {
            if ($field->searchable) {
                $searchable[] = $field;
            }
        }

        return $searchable;
    }

    /**
     * Get a setting by key.
     */
    public function getSetting(string $key): mixed
    {
        return $this->settings?->get($key);
    }

    /**
     * Get the fields as JSON.
     */
    public function getFieldsJson(): string
    {
        $fields = [];
        foreach (($this->fields ?? []) as $field) {
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
        $json = json_encode($fields, JSON_PRETTY_PRINT);

        return $json ?: '{}';
    }

    /**
     * Get the casts for the fields.
     *
     * @return array<string, string> e.g. ['is_active' => 'boolean', 'created_at' => 'datetime']
     */
    public function getCasts(): array
    {
        // TODO: Implement logic to determine casts based on field types
        return [];
    }

    /**
     * Get relations without morph relations.
     *
     * @return Relation[]
     */
    public function getRelationsWithoutMorph(): array
    {
        return array_filter(($this->relations ?? []), function (Relation $relation) {
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
        return array_filter(($this->relations ?? []), function (Relation $relation) {
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
        return array_find(($this->relations ?? []), fn ($relation) => $relation->getForeignKey() === $field->name);
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
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT);

        return $json ?: '{}';
    }

    /**
     * To array representation of the entity.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'table' => $this->getTableName(),
            'icon' => $this->icon,
            'fields' => array_map(fn ($field) => json_decode($field->toJson(), true), ($this->fields ?? [])),
            'relations' => array_map(fn ($relation) => json_decode($relation->toJson(), true), ($this->relations ?? [])),
            'filters' => array_map(fn ($filter) => json_decode($filter->toJson(), true), ($this->filters ?? [])),
            'actions' => array_map(fn (Action $action) => $action->toArray(), ($this->actions ?? [])),
            'settings' => $this->settings ? json_decode($this->settings->toJson()) : null,
        ];
    }

    /**
     * Whether the entity has images
     * defined in settings json config.
     */
    public function hasImages(): bool
    {
        return $this->settings->hasImages ?? false;
    }

    /**
     * Ensure there is at least one main field defined.
     * If not, set the first string field as main.
     */
    public function ensureMainField(): void
    {
        if ($this->hasNameField()) {
            return;
        }

        // Try to find first string field
        foreach (($this->fields ?? []) as $field) {
            if (in_array($field->type, [FieldType::STRING, FieldType::TEXT, FieldType::CHAR, FieldType::EMAIL], true)) {
                $field->asMain();

                return;
            }
        }

        // If no string field, set the first field as main
        /*        if (! empty($this->fields)) {
                    $this->fields[0]->asMain();
                }*/
    }

    /**
     * Ensure the foreign key is set, if not, add it as a new foreignId field.
     */
    public function ensureForeignKey(string $foreignKey, self $relatedEntity): void
    {
        if (! $this->hasField($foreignKey)) {
            $foreignKeyField = Field::fromConfig([
                'name' => $foreignKey,
                'type' => FieldType::FOREIGN_ID->value,
                'nullable' => true,
            ], $this);
            $this->addFieldAfterTypes($foreignKeyField, [FieldType::ID]);
        }

        // We also need to ensure the relation field is set
        $this->ensureRelationByForeignKey($foreignKey, $relatedEntity);
    }

    /**
     * Ensure the foreign key along with its relation field is set.
     */
    public function ensureForeignKeyWithRelation(Relation $relation, self $relatedEntity): void
    {
        $foreignKey = $relation->getForeignKey() ?? $this->getForeignKey();
        $this->ensureForeignKey($foreignKey, $relatedEntity);

        // TODO: Implement logic to ensure the relation field is set if needed

    }

    /**
     * Ensures relation by foreign key.
     * If it does not exist, it will be created.
     */
    public function ensureRelationByForeignKey(string $foreignKey, self $relatedEntity): void
    {
        $field = $this->getFieldByName($foreignKey);
        if ($field === null) {
            throw new InvalidArgumentException("Field with name '{$foreignKey}' not found in entity '{$this->getName()}'");
        }

        $relation = $this->getRelationByField($field);
        if (! $relation) {
            $this->addRelationByForeignKey($foreignKey, $relatedEntity);
        }
    }

    /**
     * Ensures relation by foreign key.
     * If it does not exist, it will be created.
     */
    public function ensureRelationByForeignKeyField(Field $field, self $relatedEntity): void
    {
        $this->ensureRelationByForeignKey($field->name, $relatedEntity);
    }

    /**
     * Ensures relation by pivot table.
     * If it does not exist, it will be created.
     */
    public function ensureRelationByPivotTable(string $pivotTable): void
    {
        // Check if a BELONGS_TO_MANY relation with this pivot table already exists
        $existingRelation = array_find(($this->relations ?? []), function (Relation $relation) use ($pivotTable) {
            return $relation->requiresPivotTable() && $relation->getPivotName() === $pivotTable;
        });

        if ($existingRelation) {
            return; // Relation already exists
        }

        // Create a new BELONGS_TO_MANY relation
        $relation = Relation::make(
            RelationType::BELONGS_TO_MANY,
            $this
        )
            ->localEntity($this)
            ->pivotName($pivotTable)
            ->build();

        $this->addRelation($relation);
    }

    /**
     * Index page title for this entity.
     */
    public function getIndexPageTitle(): string
    {
        return $this->getPluralName();
    }

    /**
     * Get the Inertia component name for this entity.
     * E.g. "Users/Index", "Posts/Index"
     */
    public function getInertiaComponent(): string
    {
        return $this->getDirectoryName().'/Index';
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
     * Add a relation based on the foreign key.
     * This is a placeholder for actual implementation.
     */
    private function addRelationByForeignKey(string $foreignKey, self $relatedEntity): void
    {
        // Placeholder implementation
        $relation = Relation::make(
            RelationType::BELONGS_TO,
            $this
        )
            ->localEntity($this)
            ->relatedEntity($relatedEntity)
            ->foreignKey($foreignKey)
            ->build();

        $this->addRelation($relation);
    }

    /**
     * Hook called after the entity is created from config.
     */
    private function afterCreated(): void
    {
        // Ensure there is at least one main field defined
        $this->ensureMainField();

        // Set default values for fields if needed
        // e.g., the main field should be searchable and sortable
        $this->setDefaults();
    }

    // Set default values for fields if needed
    // e.g., the main field should be searchable and sortable
    private function setDefaults(): void
    {
        foreach (($this->fields ?? []) as $field) {
            if ($field->isMain()) {
                $field->searchable = true;
                $field->sortable = true;
                $field->required = true;
            }

            // ID field should be by default hidden in forms
            if ($field->name === 'id') {
                $field->hidden = true;
                // $field->showInCard = false;
            }
        }
    }
}
