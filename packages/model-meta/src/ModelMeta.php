<?php

namespace Glugox\ModelMeta;

abstract class ModelMeta
{
    /**
     * @return Field[]
     */
    abstract public function fields(): array;

    /**
     * @return Relation[]
     */
    abstract public function relations(): array;

    /**
     * @return Filter[]
     */
    abstract public function filters(): array;

    /**
     * Returns database table name for the model.
     */
    abstract public function tableName(): string;

    /**
     * Table fields as Field objects
     *
     * @return Field[]
     */
    public function tableFieldObjects(): array
    {
        return array_filter($this->fields(), fn(Field $field) => $field->showInTable);
    }

    /**
     * Get an array of field names defined in the model meta
     * that should be visible in table views.
     *
     * @return string[]
     */
    public function tableFields(): array
    {
        // Filter fields that are marked as visible in table views
        return array_map(fn(Field $field) => $field->name, $this->tableFieldObjects());
    }

    /**
     * Form fields as Field objects
     *
     * @return Field[]
     */
    public function formFieldObjects(): array
    {
        return array_filter($this->fields(), fn(Field $field) => $field->showInForm);
    }

    /**
     * Get an array of field names defined in the model meta
     * that should be visible in form views.
     *
     * @return string[]
     */
    public function formFields(): array
    {
        // Filter fields that have showInForm = true
        return array_map(fn(Field $field) => $field->name, $this->formFieldObjects());
    }

    /**
     * Relations as Relation objects
     *
     * @return string[]
     */
    public function relationsNames(): array
    {
        return array_map(fn(Relation $relation) => $relation->name, $this->relations());
    }

    /**
     * Get array of searchable field objects defined in the model meta.
     *
     * @return Field[]
     */
    public function searchableFieldObjects(): array
    {
        return array_filter($this->fields(), fn(Field $field) => $field->searchable);
    }

    /**
     * Get array of searchable field names defined in the model meta.
     *
     * @return string[]
     */
    public function searchableFields(): array
    {
        return array_map(fn(Field $field) => $field->name, $this->searchableFieldObjects());
    }

    /**
     * Get array of fields that are representing as name (e.g. for dropdowns).
     *
     * @return Field[]
     */
    public function nameFieldObjects(): array
    {
        return array_filter($this->fields(), fn(Field $field) => $field->main);
    }

    /**
     * Get array of field names that are representing as name (e.g. for dropdowns).
     *
     * @return string[]
     */
    public function nameFields(): array
    {
        return array_map(fn(Field $field) => $field->name, $this->nameFieldObjects());
    }

    /**
     * Rules for validation.
     *
     * @return array{store: array<string, string[]>, update: array<string, string[]>} The validation rules.
     */
    public function rules(?string $recordId = null): array
    {
        return new ModelMetaRulesGenerator($this)->generate($recordId);
    }

    /**
     * Get filters for field
     *
     * @param  string  $fieldName
     * @return Filter[]
     */
    public function filtersForField(string $fieldName): array
    {
        return array_filter($this->filters(), fn(Filter $filter) => $filter->name === $fieldName);
    }

}