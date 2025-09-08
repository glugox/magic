<?php

namespace Glugox\Magic\Helpers;

use Glugox\Magic\Support\Config\Entity;

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
    public static function getRelationNamesString(Entity $entity, $relationType = null): string
    {
        $relations = $entity->getRelations($relationType);
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
}
