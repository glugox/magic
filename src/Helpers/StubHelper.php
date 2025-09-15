<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\RelationType;
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
     *
     * @deprecated Use ModelMeta::searchableFields() instead
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
     *
     * @deprecated Use ModelMeta::tableFields() instead
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

        // Use existing applyReplacements() method to inject variables
        return self::applyReplacements($stub, $replacements);
    }
}
