<?php

namespace App\Meta;

final class Field
{
    public string $name;

    public FieldType $type;

    public bool $nullable;

    public bool $showInTable;

    public bool $showInForm;

    public bool $searchable;

    public function __construct(
        string $name,
        FieldType $type,
        bool $nullable = false,
        bool $showInTable = true,
        bool $showInForm = true,
        bool $searchable = false,
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->showInTable = $showInTable;
        $this->showInForm = $showInForm;
        $this->searchable = $searchable;
    }

    /**
     * Fluent setter: mark field as nullable
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Fluent setter: show field in table
     */
    public function showInTable(bool $show = true): self
    {
        $this->showInTable = $show;

        return $this;
    }

    /**
     * Fluent setter: show field in form
     */
    public function showInForm(bool $show = true): self
    {
        $this->showInForm = $show;

        return $this;
    }

    /**
     * Fluent setter: mark field as searchable
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Helper: check if this is a BELONGS_TO field
     */
    public function isBelongsTo(): bool
    {
        return $this->type === FieldType::BELONGS_TO;
    }

    /**
     * Helper: check if this is a "name" field
     */
    public function isName(): bool
    {
        return in_array($this->name, ['name', 'title', 'label'], true);
    }
}
