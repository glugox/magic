<?php

namespace Glugox\Magic\Support\Config\Readers;

use Glugox\Magic\Enums\GraphQlType;
use Glugox\Magic\Support\Config\App;
use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\Normalizer\GraphQlTypeNormalizer;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;
use Glugox\Magic\Support\TypeHelper;
use RuntimeException;

class SchemaReader
{
    /**
     * @var App Application configuration for general settings like name, and version.
     */
    protected App $app; // / CHANGE: Added population from SDL

    /**
     * @var array <Entity> Loaded entities from GraphQL SDL
     */
    protected array $entities = [];

    public function __construct(
        protected GraphQlTypeNormalizer $typeNormalizer,
        protected TypeHelper $typeHelper
    ) {
        $this->app = new App('');
    }

    /**
     * Load SDL string into typed Entities
     */
    public function load(string $sdl): void
    {
        $this->entities = [];

        // Keep unmatched GraphQL types for later processing
        $unmatchedTypes = [];

        // Match each type block
        preg_match_all('/type\s+(\w+)(\s+@config)?\s*{([^}]*)}/s', $sdl, $matches, PREG_SET_ORDER); // / CHANGE: support optional @config

        foreach ($matches as $match) {
            $entityName = $match[1];
            $isConfig = mb_trim($match[2]) === '@config'; // / CHANGE
            $body = mb_trim($match[3]);

            if ($isConfig && $entityName === 'App') { // / CHANGE
                $this->populateApp($body); // / CHANGE

                continue; // / CHANGE: skip adding App as normal Entity
            }

            $entity = new Entity($entityName);

            // Split lines and parse fields
            $entityLines = preg_split('/\r?\n/', $body);
            if ($entityLines === false) {
                throw new RuntimeException("Failed to split entity body into lines for entity: {$entityName}");
            }

            foreach ($entityLines as $line) {
                $line = mb_trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue; // skip empty lines / comments
                }

                [$fieldName, $rest] = array_map('trim', explode(':', $line, 2));
                // Split type from directives
                if (preg_match('/^([^\s]+)(.*)$/', $rest, $typeMatch)) {
                    $rawType = mb_trim($typeMatch[1]);
                    $directivesStr = mb_trim($typeMatch[2]);
                    $directives = $directivesStr === '' ? [] : preg_split('/\s+/', $directivesStr);
                } else {
                    throw new RuntimeException("Cannot parse field line: {$line}");
                }

                // Scalar field detection
                $graphQlType = GraphQlType::tryFrom(mb_trim($rawType, '!')) ?? null;

                if ($graphQlType === null) {
                    // Store the line to try to parse later as relation
                    $unmatchedTypes[] = ['entity' => $entity, 'fieldName' => $fieldName, 'rawType' => $rawType, 'directives' => $directives];

                    continue; // Skip unmatched types for now
                }

                $fieldType = $this->typeNormalizer->normalize($graphQlType);

                // Read default value from directives if present
                $defaultVal = null;
                if ($directives && preg_match('/@default\((.*?)\)/', implode(' ', $directives), $defaultMatch)) {
                    $defaultVal = mb_trim($defaultMatch[1]);
                    $defaultVal = $this->typeHelper->castValueForFieldType($fieldType, $defaultVal);
                }

                // Read min max from directives if present
                $min = null;
                $max = null;
                if ($directives) {
                    foreach ($directives as $directive) {
                        if (preg_match('/@min\((.*?)\)/', $directive, $minMatch)) {
                            $min = mb_trim($minMatch[1]);
                            $min = $this->typeHelper->castValueForFieldType(FieldType::INTEGER, $min);
                        }
                        if (preg_match('/@max\((.*?)\)/', $directive, $maxMatch)) {
                            $max = mb_trim($maxMatch[1]);
                            $max = $this->typeHelper->castValueForFieldType(FieldType::INTEGER, $max);
                        }
                    }
                }

                $field = new Field(
                    name: $fieldName,
                    type: $fieldType,
                    entityRef: $entity,
                    required: str_ends_with($rawType, '!'),
                    sortable: $directives && in_array('@sort', $directives),
                    searchable: $directives && in_array('@search', $directives),
                    nullable: ! str_ends_with($rawType, '!'),
                    default: $defaultVal,
                    min: $min,
                    max: $max

                );

                $entity->addField($field);
            }

            $this->entities[$entityName] = $entity;
        }

        // After processing all fields, handle unmatched types as relations
        foreach ($unmatchedTypes as $unmatched) {
            $unmatchedEntity = $unmatched['entity'];
            $unmatchedEntityName = $unmatchedEntity->getName();
            $unmatchedFieldName = $unmatched['fieldName'];
            $unmatchedRawType = $unmatched['rawType'];
            $unmatchedDirectives = $unmatched['directives'];

            // Relation detection
            if (preg_match('/^(\w+)!?$/', $unmatchedRawType, $unmatchedListMatches)) {
                $relatedEntityName = $unmatchedListMatches[1];

                // Check directives for relation type
                if (empty($unmatchedDirectives)) {
                    throw new RuntimeException("Relation field '{$unmatchedFieldName}' in entity '{$unmatchedEntityName}' must have relation directive (e.g. @hasMany)");
                }
                $relationType = $this->detectRelationType($unmatchedDirectives);

                // Parse relation name from directives if present
                $relationName = $unmatchedFieldName;
                foreach ($unmatchedDirectives as $directive) {
                    if (preg_match('/@name\((.*?)\)/', $directive, $relationMatch)) {
                        $relationName = mb_trim($relationMatch[1]);
                        // strip quotes if any
                        $relationName = mb_trim($relationName, '"\'');
                    }
                }

                // {arse foreignKey if present in directives
                $foreignKey = null;
                foreach ($unmatchedDirectives as $directive) {
                    if (preg_match('/@foreignKey\((.*?)\)/', $directive, $fkMatch)) {
                        $foreignKey = mb_trim($fkMatch[1]);
                        // strip quotes if any
                        $foreignKey = mb_trim($foreignKey, '"\'');
                    }
                }
                // match @localKey if present in directives
                $localKey = null;
                foreach ($unmatchedDirectives as $directive) {
                    if (preg_match('/@localKey\((.*?)\)/', $directive, $lkMatch)) {
                        $localKey = mb_trim($lkMatch[1]);
                        // strip quotes if any
                        $localKey = mb_trim($localKey, '"\'');
                    }
                }

                $relation = new Relation(
                    type: $relationType,
                    localEntity: $unmatchedEntity,
                    entityName: $relatedEntityName,
                    foreignKey: $foreignKey,
                    localKey: $localKey,
                    relationName: $relationName
                );

                if (! isset($this->entities[$unmatchedEntityName])) {
                    throw new RuntimeException("Entity '{$unmatchedEntityName}' not found when adding relation field '{$unmatchedFieldName}'");
                }

                $this->entities[$unmatchedEntityName]->addRelation($relation);
            }
        }
    }

    /**
     * @return App Returns application configuration from SDL.
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * Returns loaded entities from SDL by entity name.
     *
     * @return array<string,Entity> Returns loaded entities from SDL
     */
    public function getEntities(): array
    {
        return array_values($this->entities);
    }

    /**
     * Populate App object from SDL body
     */
    protected function populateApp(string $body): void // / CHANGE
    {
        $lines = preg_split('/\r?\n/', $body);
        if ($lines === false) {
            throw new RuntimeException('Failed to split App body into lines');
        }

        foreach ($lines as $line) {
            $line = mb_trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$fieldName, $rest] = array_map('trim', explode(':', $line, 2));
            $default = null;

            // Parse directives
            if (preg_match_all('/@(\w+)\((.*?)\)/', $rest, $dirMatches, PREG_SET_ORDER)) {
                foreach ($dirMatches as $d) {
                    if ($d[1] === 'default') {
                        $val = mb_trim($d[2]);
                        // simple cast to scalar type
                        if ($val === 'true') {
                            $val = true;
                        } elseif ($val === 'false') {
                            $val = false;
                        } elseif (is_numeric($val)) {
                            $val = $val + 0;
                        } elseif (str_starts_with($val, '"') && str_ends_with($val, '"')) {
                            $val = mb_trim($val, '"');
                        }

                        $default = $val;
                    }
                }
            }

            if (property_exists($this->app, $fieldName)) {
                $this->app->$fieldName = $default;
            }
        }
    }

    /**
     * Detect relation type from SDL directives
     *
     * @param  string[]  $directives
     */
    protected function detectRelationType(array $directives): RelationType
    {
        if (in_array('@hasMany', $directives, true)) {
            return RelationType::HAS_MANY;
        }

        if (in_array('@hasOne', $directives, true)) {
            return RelationType::HAS_ONE;
        }

        if (in_array('@belongsTo', $directives, true)) {
            return RelationType::BELONGS_TO;
        }

        if (in_array('@belongsToMany', $directives, true)) {
            return RelationType::BELONGS_TO_MANY;
        }

        // Morph relations
        if (in_array('@morphMany', $directives, true)) {
            return RelationType::MORPH_MANY;
        }

        if (in_array('@morphTo', $directives, true)) {
            return RelationType::MORPH_TO;
        }

        throw new RuntimeException('Cannot detect relation type from directives: '.implode(', ', $directives));
    }
}
