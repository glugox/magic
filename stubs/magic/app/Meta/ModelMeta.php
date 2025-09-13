<?php

namespace App\Meta;

abstract class ModelMeta
{
    /**
     * Fully qualified model class
     */
    public static string $model;

    /**
     * Return all fields (must be implemented in concrete subclass)
     *
     * @return Field[]
     */
    abstract public static function getFields(): array;

    /**
     * Return model relations (must be implemented)
     *
     * @return Relation[]
     */
    abstract public static function getRelations(): array;

    // ----------------- Static helper methods -----------------

    /**
     * Return fields for table/list view
     *
     * @return Field[]
     */
    public static function getTableFields(): array
    {
        return array_filter(static::getFields(), fn (Field $f) => $f->showInTable);
    }

    /**
     * Return fields for create/edit form
     *
     * @return Field[]
     */
    public static function getFormFields(): array
    {
        return array_filter(static::getFields(), fn (Field $f) => $f->showInForm);
    }

    /**
     * Return searchable fields
     *
     * @return Field[]
     */
    public static function getSearchableFields(): array
    {
        return array_filter(static::getFields(), fn (Field $f) => $f->searchable);
    }

    /**
     * Return foreign keys only
     *
     * @return string[]
     */
    public static function getForeignKeys(): array
    {
        return array_map(fn (Relation $r) => $r->foreignKey ?? $r->name.'_id', static::getRelations());
    }

    /**
     * Return fillable fields for mass assignment
     *
     * @return Field[]
     */
    public static function getFillableFields(): array
    {
        return array_filter(
            static::getFields(),
            fn (Field $f) => ! in_array($f->name, ['id', 'created_at', 'updated_at'], true)
        );
    }

    /**
     * Return names of fields for table view
     *
     * @return string[]
     */
    public static function getTableFieldsNames(): array
    {
        return array_map(fn (Field $f) => $f->name, static::getTableFields());
    }

    /**
     * Return names of fields for form view
     *
     * @return string[]
     */
    public static function getFormFieldsNames(): array
    {
        return array_map(fn (Field $f) => $f->name, static::getFormFields());
    }
}
