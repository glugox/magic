<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Field;
use Glugox\Magic\Support\Config\FieldType;
use Glugox\Magic\Support\Config\RelationType;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StubHelper
{
    /**
     * Build PHP array string for a list of field names
     *
     * Example output: "['id','name','email']"
     *
     * @param  string[]  $fields
     */
    public static function buildPhpArrayString(array $fields, bool $includeId = true): string
    {
        if ($includeId && ! in_array('id', $fields)) {
            array_unshift($fields, 'id');
        }

        if (empty($fields)) {
            return '[]';
        }

        return "['".implode("','", $fields)."']";
    }

    /**
     * Get entity "select fields" string for stub
     *
     * Example output: "['id','name','email']"
     */
    public static function getSelectFieldsString(Entity $entity): string
    {
        $fields = $entity->getNameFieldsNames() ?: ['name'];

        return self::buildPhpArrayString($fields);
    }

    /**
     * Get related entity "select fields" string for stub
     *
     * Example output: "['id','name','email']"
     */
    public static function getSelectRelatedFieldsString(Entity $entity): string
    {
        $fields = $entity->getNameFieldsNames() ?: ['name'];

        return self::buildPhpArrayString($fields);
    }

    /**
     * Get searchable fields string for stub
     *
     * Example output: "['name','email']"
     */
    public static function getSearchableFieldsString(Entity $entity): string
    {
        $searchable = array_filter($entity->getFields(), fn ($f) => $f->searchable);
        $names = array_map(fn ($f) => $f->name, $searchable);

        return self::buildPhpArrayString($names, false);
    }

    /**
     * Get table fields string (for index listing) for stub
     *
     * Example output: "['id','name','email','created_at']"
     */
    public static function getTableFieldsString(Entity $entity): string
    {
        $fields = $entity->getTableFieldsNames();

        return self::buildPhpArrayString($fields, false);
    }

    /**
     * Get eager loaded relations string for stub
     *
     * Example output: "['relationName:field1,field2','otherRelation:field1']"
     */
    public static function getRelationNamesString(Entity $entity, $relationType = null, bool $withMorphs = false): string
    {
        $relations = $entity->getRelations($relationType, $withMorphs ? null : [
            RelationType::MORPH_TO,
            RelationType::MORPH_MANY,
            RelationType::MORPH_ONE,
            RelationType::MORPH_TO_MANY,
            RelationType::MORPHED_BY_MANY,
        ]);

        $names = array_map(fn ($r) => $r->getRelationName().':'.$r->getEagerFieldsStr(), $relations);

        return empty($names) ? '[]' : "['".implode("','", $names)."']";
    }

    /**
     * Apply replacements to a stub.
     *
     * $replacements keys can be either 'key' or '{{key}}'
     * This method will ensure keys are replaced as '{{key}}' in the stub.
     *
     * Example:
     *  applyReplacements($stub, ['resourceClass' => 'UserResource', '{{fields}}' => $fieldsStr])
     */
    public static function applyReplacements(string $stub, array $replacements): string
    {
        // Normalize keys to {{key}} form
        $normalizedKeys = [];
        $values = [];

        foreach ($replacements as $k => $v) {
            $key = $k;
            if (! str_starts_with($key, '{{') || ! str_ends_with($key, '}}')) {
                $key = '{{'.mb_trim($key, '{} ').'}}';
            }
            $normalizedKeys[] = $key;
            $values[] = $v;
        }

        return str_replace($normalizedKeys, $values, $stub);
    }

    /**
     * Load a stub file and apply replacements using applyReplacements().
     *
     * @param  string  $stubPath  Relative path inside the stubs folder, e.g., 'vue/index.stub'
     * @param  array  $replacements  Key-value pairs to replace in the stub
     */
    public static function loadStub(string $stubPath, array $replacements = []): string
    {
        // Resolve full stub path (assuming stubs are inside package resources/stubs/)
        $fullPath = __DIR__.'/../../stubs/'.$stubPath;

        if (! file_exists($fullPath)) {
            throw new RuntimeException("Stub file not found: {$fullPath}");
        }

        $stub = file_get_contents($fullPath);
        if ($stub === false) {
            throw new RuntimeException("Failed to read stub file: {$fullPath}");
        }

        // Use existing applyReplacements() method to inject variables
        return self::applyReplacements($stub, $replacements);
    }

    /**
     * Write Pest form filling code for a given Entity.
     */
    public static function writePestFormFields(Entity $entity): string
    {
        Log::channel('magic')->info("Generating Pest form fields for entity: {$entity->getName()}");

        $fieldsCode = [];
        foreach ($entity->getFormFields() as $field) {
            $fieldsCode[] = self::writePestFormField($field, $entity);
        }

        return implode("\n            ", $fieldsCode);
    }

    /**
     * Write factories call code for a given Entity.
     * This will generate code to create related entities before creating the main entity.
     */
    public static function writeFactoriesCallFor(Entity $entity): string
    {
        Log::channel('magic')->info("Generating factories call for entity: {$entity->getName()}");
        $factoryLines = [];
        foreach ($entity->getRelations(RelationType::BELONGS_TO) as $relation) {
            if ($relation->hasRelatedEntity()) {
                $relatedEntity = $relation->getRelatedEntity();
                $relatedModelClass = $relatedEntity->getClassName();
                $factoryLines[] = "{$relatedModelClass}::factory()->create();";
            }
        }

        return implode("\n    ", $factoryLines);
    }

    /**
     * Write Pest form field filling code for a given field.
     */
    public static function writePestFormField(Field $field, Entity $entity): string
    {
        Log::channel('magic')->info("Generating Pest form field: {$field->name} of type: {$field->type->value} for entity: {$entity->getName()}");

        $fieldName = $field->name;
        $fieldType = $field->type;

        switch ($fieldType) {
            case FieldType::STRING:
            case FieldType::TEXT:
                return "->type('{$fieldName}', 'Test {$entity->name} {$fieldName}')";
            case FieldType::EMAIL:
                return "->type('{$fieldName}', 'example@example.com')";
            case FieldType::INTEGER:
            case FieldType::FLOAT:
            case FieldType::DECIMAL:
            case FieldType::URL:
                return "->type('{$fieldName}', '123')";
            case FieldType::DATE:
            case FieldType::DATETIME:
            case FieldType::TIME:
            case FieldType::TIMESTAMP:
                return "// ->type('{$fieldName}', '2024-01-01 12:00:00') // Adjust date format as needed";
            case FieldType::PASSWORD:
                return "->type('{$fieldName}', 'password')";
            case FieldType::BOOLEAN:
                return "->check('{$fieldName}')";
            case FieldType::FOREIGN_ID:
            case FieldType::BELONGS_TO:
                // For foreign keys, select the first related entity
                Log::channel('magic')->info(" >> Field {$fieldName} is a foreign key, generating select code");
                if ($field->isForeignKey()) {
                    $relation = $entity->getRelationByField($field);
                    $hasRelatedEntity = $relation?->hasRelatedEntity();
                    if ($hasRelatedEntity) {
                        $relatedEntity = $entity->getRelationByField($field)?->getRelatedEntity();
                        if (! empty($relatedEntity)) { // This is done by $hasRelatedEntity, but we need to satisfy phpstan
                            return "->select('{$fieldName}', {$relatedEntity->getClassName()}::first()->id)";
                        }
                    } else {
                        Log::channel('magic')->warning("Related entity not found for foreign key field {$fieldName}");
                    }
                } else {
                    Log::channel('magic')->warning("Field {$fieldName} is not marked as foreign key but has type FOREIGN_ID or BELONGS_TO");
                }

                return '// Relation not found for FieldType::FOREIGN_ID';

            case FieldType::ENUM:
                return "->select('{$fieldName}', '{$field->getFirstEnumOptionValue()}')";
            case 'file':
            case 'image':
                return "->attach('{$fieldName}', base_path('tests/Fixtures/test_file.txt'))";
            default:
                return "// Unsupported field type: {$fieldType->value}";
        }

    }
}
