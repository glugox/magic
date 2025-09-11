<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Enums\GraphQlType;
use Glugox\Magic\Support\Config\App;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Enum;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Normalizer\GraphQlTypeNormalizer;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use RuntimeException;

class GraphQlHelper
{
    protected GraphQlTypeNormalizer $typeNormalizer;

    /**
     * Constructor
     */
    public function __construct(GraphQlTypeNormalizer $typeNormalizer)
    {
        $this->typeNormalizer = $typeNormalizer;
    }

    // --- APP related ---

    /**
     * Populate App instance from SDL body
     *
     * @param App $app The App instance to populate
     * @param string $body The body of the App type definition
     */
    public function populateApp(App $app, string $body): void
    {
        $lines = preg_split('/\r?\n/', $body);
        if ($lines === false) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, ':')) {
                continue; // skip non-field lines (or opening/closing braces)
            }

            [$fieldName, $rest] = array_map('trim', explode(':', $line, 2));
            $default = $this->extractDefaultFromRest($rest);

            if (property_exists($app, $fieldName)) {
                $app->$fieldName = $default;
            }
        }
    }

    /**
     * Extract default value from a line's rest part
     *
     * @param string $rest The part of the line after the colon
     * @return mixed The extracted default value or null if not found
     */
    protected function extractDefaultFromRest(string $rest): mixed
    {
        if (preg_match_all('/@default\((.*?)\)/', $rest, $matches, PREG_SET_ORDER)) {
            $val = trim($matches[0][1] ?? '');
            if ($val === 'true') return true;
            if ($val === 'false') return false;
            if (is_numeric($val)) return $val + 0;
            return trim($val, '"\'');
        }
        return null;
    }

    // --- ENTITY related ---

    /**
     * Extract an Entity from a regex match
     *
     * @param array{1: string, 2: string|null, 3: string, 4: string} $match
     *
     * @return Entity The extracted Entity object
     * @throws RuntimeException if field parsing fails
     */
    public function extractEntity(array $match): Entity
    {
        $name = $match[2];
        $body = trim($match[4]);
        $entity = new Entity($name);

        $fields = $this->extractFields($entity, $body);
        foreach ($fields as $field) {
            $entity->addField($field);
        }

        return $entity;
    }

    /**
     * Extract fields from entity body
     *
     * @param Entity $entity The entity to which the fields belong
     * @param string $body The body of the entity definition
     *
     * @return Field[] Array of Field objects
     * @throws RuntimeException if a field line cannot be parsed
     */
    public function extractFields(Entity $entity, string $body): array
    {
        $lines = preg_split('/\r?\n/', $body);
        if ($lines === false) return [];

        $fields = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            [$fieldName, $rest] = array_map('trim', explode(':', $line, 2));
            if (!preg_match('/^([^\s]+)(.*)$/', $rest, $typeMatch)) {
                throw new RuntimeException("Cannot parse field line: {$line}");
            }

            $rawType = trim($typeMatch[1]);
            $directivesStr = trim($typeMatch[2]);
            $directives = $directivesStr === '' ? [] : (preg_split('/\s+/', $directivesStr) ?: []);

            $field = $this->parseFieldType($entity, $fieldName, $rawType, $directives);
            if ($field !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Extract relations for a given entity from all entities
     *
     *@param array{1: string, 2: string|null, 3: string, 4: string} $match
     * @param array<string, Entity> $allEntities All available entities to reference
     * @return Relation[] Array of Relation objects
     */
    public function extractRelationsForEntity(array $match, array $allEntities): array
    {
        $relations = [];

        $entityName = trim($match[2]);
        if (!isset($allEntities[$entityName])) {
            return $relations; // Entity not found
        }
        $entity = $allEntities[$entityName];
        $body = trim($match[4]);
        $lines = preg_split('/\r?\n/', $body);
        if ($lines === false) return $relations;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, ':')) continue;

            [$fieldName, $rest] = array_map('trim', explode(':', $line, 2));
            if (!preg_match('/^([^\s]+)(.*)$/', $rest, $typeMatch)) {
                continue; // skip unparsable lines
            }

            $rawType = trim($typeMatch[1]);
            $directivesStr = trim($typeMatch[2]);
            $directives = $directivesStr === '' ? [] : (preg_split('/\s+/', $directivesStr) ?: []);

            // Only process fields that have relation directives
            if (empty(array_intersect($directives, [
                '@hasMany', '@hasOne', '@belongsTo', '@belongsToMany', '@morphMany', '@morphTo'
            ]))) {
                if($rawType === '@morphTo') {
                    // Special case for morphTo without type
                    $relation = new Relation(
                        type: RelationType::MORPH_TO,
                        localEntity: $entity,
                        relationName: $fieldName
                    );
                    $relations[] = $relation;
                }
                continue;
            }

            // Parse relation type
            try {
                $relationType = $this->detectRelationType($directives);
            } catch (RuntimeException) {
                continue; // Skip if relation type cannot be detected
            }

            // Parse foreignKey and localKey
            [$foreignKey, $localKey, $relatedForeignKey] = $this->parseKeys($directives);

            // Determine related entity name from field type
            $relatedEntityName = trim($rawType, '[]!'); // Remove list and non-null markers

            if (!isset($allEntities[$relatedEntityName])) {
                continue; // Related entity not found
            }

            // Parse relation name from directives or use field name as default
            $relationName = $this->parseRelationName($directives, $fieldName);

            $relation = new Relation(
                type: $relationType,
                localEntity: $entity,
                relatedEntityName: $relatedEntityName,
                foreignKey: $foreignKey,
                localKey: $localKey,
                relatedKey: $relatedForeignKey,
                relationName: $relationName
            );

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * Parse field type and attributes from raw type and directives
     *
     * @param Entity $entity The entity to which the field belongs
     * @param string $fieldName Name of the field
     * @param string $rawType Raw GraphQL type (e.g. "String!", "[Int]", etc.)
     * @param string[] $directives Array of directives applied to the field
     * @return Field|null Parsed Field object
     */
    public function parseFieldType(Entity $entity, string $fieldName, string $rawType, array $directives): ?Field
    {
        $graphQlType = GraphQlType::tryFrom(trim($rawType, '!'));
        if ($graphQlType === null) {

            // Try to see if it's a registered enum
            $typeName = trim($rawType, '[]!');
            if ($this->isEnum($typeName)) {
                $fieldType = FieldType::ENUM;
                return new Field(
                    name: $fieldName,
                    type: $fieldType,
                    entityRef: $entity,
                    required: $this->isRequired($rawType),
                    nullable: $this->isNullable($rawType),
                    default: $this->extractDefault($fieldType, $directives),
                    sortable: in_array('@sort', $directives),
                    searchable: in_array('@search', $directives),
                    values: $this->getEnumValue($typeName)?->values,
                );
            }

            return null;
        }

        $fieldType = $this->typeNormalizer->normalize($graphQlType);

        $defaultVal = $this->extractDefault($fieldType, $directives);
        [$min, $max] = $this->extractMinMax($directives);

        return new Field(
            name: $fieldName,
            type: $fieldType,
            entityRef: $entity,
            required: $this->isRequired($rawType),
            nullable: $this->isNullable($rawType),
            default: $defaultVal,
            sortable: in_array('@sort', $directives),
            searchable: in_array('@search', $directives),
            min: $min,
            max: $max
        );
    }

    /**
     * Extract enum values from directives
     *
     * @param string[] $directives
     * @return string[] Array of enum values or empty array if not found
     */
    public function extractEnumValues(array $directives): array
    {
        foreach ($directives as $directive) {
            if (preg_match('/@values\((.*?)\)/', $directive, $matches)) {
                return array_map('trim', explode(',', $matches[1]));
            }
        }
        return [];
    }

    /**
     * Extract min and max values from directives
     *
     * @param string[] $directives
     * @return array{0: int|null, 1: int|null} [min, max]
     */
    public function extractMinMax(array $directives): array
    {
        $min = $max = null;
        foreach ($directives as $directive) {
            if (preg_match('/@min\((.*?)\)/', $directive, $match)) $min = (int) $match[1];
            if (preg_match('/@max\((.*?)\)/', $directive, $match)) $max = (int) $match[1];
        }
        return [$min, $max];
    }

    /**
     * Extract default value from directives based on field type
     *
     * @param FieldType $fieldType
     * @param string[] $directives
     * @return mixed Default value or null if not found
     */
    public function extractDefault(FieldType $fieldType, array $directives): mixed
    {
        foreach ($directives as $directive) {
            if (preg_match('/@default\((.*?)\)/', $directive, $match)) {
                $val = trim($match[1]);
                if ($fieldType === FieldType::BOOLEAN) return $val === 'true';
                if (is_numeric($val)) return $val + 0;
                return trim($val, '"\'');
            }
        }
        return null;
    }

    // --- ENUM related ---

    /**
     * Registered enums
     *
     * @var array<string, Enum> Map of enum name to Enum instance
     */
    protected array $enums = [];

    /**
     * Register enum type and its values
     *
     * @param string $enumName Name of the enum type
     * @param Enum $enum
     */
    public function registerEnum(string $enumName, Enum $enum): void
    {
        $this->enums[$enumName] = $enum;
    }

    /**
     * Get enum values by name
     *
     * @return ?Enum Enum instance or null if not found
     */
    public function getEnumValue(string $enumName): ?Enum
    {
        return $this->enums[$enumName] ?? null;
    }

    /**
     * Check if a type is registered enum
     */
    public function isEnum(string $typeName): bool
    {
        return isset($this->enums[$typeName]);
    }

    /**
     * Optimistic: extract enum definition from SDL match
     *
     * @param array{1: string, 2: string, 3: string, 4: string} $match
     * @return Enum
     */
    public function extractEnum(array $match): Enum
    {
        // match[2] = enum name, match[4] = enum body
        $name = $match[2];
        $bodyLines = preg_split('/\r?\n/', trim($match[4])) ?: [];

        $values = [];
        foreach ($bodyLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $values[] = $line;
        }

        $enum = Enum::fromConfig([
            'name' => $name,
            'values' => $values
        ]);

        $this->registerEnum($name, $enum);

        return $enum;
    }

    /**
     * @return Enum[] All registered enums
     */
    public function getEnums(): array
    {
        return $this->enums;
    }

    /**
     * Detect relation type based on directives
     *
     * @param string[] $directives
     * @throws RuntimeException if no relation directive found
     */
    public function detectRelationType(array $directives): RelationType
    {
        return match (true) {
            in_array('@hasMany', $directives) => RelationType::HAS_MANY,
            in_array('@hasOne', $directives) => RelationType::HAS_ONE,
            in_array('@belongsTo', $directives) => RelationType::BELONGS_TO,
            in_array('@belongsToMany', $directives) => RelationType::BELONGS_TO_MANY,
            in_array('@morphMany', $directives) => RelationType::MORPH_MANY,
            in_array('@morphTo', $directives) => RelationType::MORPH_TO,
            default => throw new RuntimeException('Cannot detect relation type')
        };
    }

    /**
     * Parse foreignKey and localKey from directives
     *
     * @param string[] $directives
     * @return array{0: string|null, 1: string|null, 2: string|null} [foreignKey, localKey]
     */
    public function parseKeys(array $directives): array
    {
        $foreignKey = $localKey = $relatedForeignKey = null;
        foreach ($directives as $directive) {
            if (preg_match('/@foreignKey\((.*?)\)/', $directive, $match)) $foreignKey = trim($match[1], '"\'');
            if (preg_match('/@localKey\((.*?)\)/', $directive, $match)) $localKey = trim($match[1], '"\'');
            if (preg_match('/@relatedForeignKey\((.*?)\)/', $directive, $match)) $relatedForeignKey = trim($match[1], '"\'');
        }
        return [$foreignKey, $localKey, $relatedForeignKey];
    }

    /**
     * Parse relation name from directives, or return default if not specified
     *
     * @param string[] $directives
     * @param string $defaultName
     * @return string
     */
    public function parseRelationName(array $directives, string $defaultName): string
    {
        /** @noinspection PhpLoopCanBeConvertedToArrayAnyInspection */
        foreach ($directives as $directive) {
            if (preg_match('/@name\((.*?)\)/', $directive, $match)) return trim($match[1], '"\'');
        }
        return $defaultName;
    }

    // --- UTILITIES ---

    /**
     * Check if a GraphQL type is required (non-nullable)
     */
    public function isRequired(string $rawType): bool
    {
        return str_ends_with($rawType, '!');
    }

    /**
     * Check if a GraphQL type is nullable
     */
    public function isNullable(string $rawType): bool
    {
        return !$this->isRequired($rawType);
    }

    /**
     * Check if a field represents a relation (non-scalar type)
     */
    protected function isRelationField(Field $field): bool
    {
        return !in_array($field->type, [
            FieldType::STRING,
            FieldType::INTEGER,
            FieldType::FLOAT,
            FieldType::BOOLEAN,
        ]);
    }
}
