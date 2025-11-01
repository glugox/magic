<?php

namespace Glugox\ModelMeta;

use Illuminate\Support\Str;

/**
 * Represents metadata for a single field in a model or resource.
 */
class Field
{
    /**
     * @param FieldType $type
     * @param string $name
     * @param string|null $label
     * @param string[] $rules
     * @param bool $nullable
     * @param mixed|null $default
     * @param bool $required
     * @param bool $unique
     * @param bool $sometimes
     * @param string|null $comment
     * @param bool $sortable
     * @param bool $searchable
     * @param bool $main
     * @param bool $showInTable
     * @param bool $showInForm
     * @param int|float|null $min
     * @param int|float|null $max
     * @param bool $readonly
     * @param bool $hidden
     * @param float|null $step
     */
    public function __construct(
        public FieldType $type,
        public string $name,
        public ?string $label = null,

        /** Validation rules array (Laravel style) */
        public array $rules = [],

        /** Whether the field is nullable */
        public bool $nullable = false,

        /** Default value, can be scalar or callable */
        public mixed $default = null,

        /** Whether the field is required */
        public bool $required = false,

        /** whether the field is unique */
        public bool $unique = false,

        /** Whether the field is validated sometimes (conditional) */
        public bool $sometimes = false,

        /** Database comment or field description */
        public ?string $comment = null,

        /** Whether field is sortable in tables */
        public bool $sortable = false,

        /** Whether field is searchable */
        public bool $searchable = false,

        /** Whether this field is considered the "name" of the entity */
        public bool $main = false,

        /** Show this field in table index */
        public bool $showInTable = true,

        /** Show this field in forms */
        public bool $showInForm = true,

        /** Min value for numeric fields */
        public int|float|null $min = null,

        /** Max value for numeric fields */
        public int|float|null $max = null,

        /** Readonly field, not editable */
        public bool $readonly = false,

        /** Hidden field, exists in model but not in UI */
        public bool $hidden = false,

        /** Precision for decimal/float fields */
        public ?float $step = null,

        /** referenced database table */
        public ?string $table = null,
    ) {}

    /**
     * Fluent setter for validation rules.
     *
     * @param string[] $rules Array of Laravel validation rules (e.g. ['required', 'string', 'max:255'])
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Fluent setter for required.
     */
    public function required(?bool $required = null): self
    {
        $this->required = $required ?? true;

        // If required, automatically disable nullable
        if ($this->required) {
            $this->nullable = false;
            $this->rules = array_filter($this->rules, fn($r) => $r !== 'nullable');

            // reset array keys
            $this->rules = array_values($this->rules);

            if (!in_array('required', $this->rules, true)) {
                $this->rules[] = 'required';
            }
        }

        return $this;
    }

    /**
     * Fluent setter for nullable.
     */
    public function nullable(?bool $nullable = null): self
    {
        $this->nullable = $nullable ?? true;

        // If nullable, remove 'required' rule if present
        if ($this->nullable) {
            $this->required = false;
            $this->rules = array_filter($this->rules, fn($r) => $r !== 'required');

            // reset array keys
            $this->rules = array_values($this->rules);

            if (!in_array('nullable', $this->rules, true)) {
                $this->rules[] = 'nullable';
            }
        }

        return $this;
    }

    /**
     * Fluent setter for default value.
     */
    public function default(mixed $default): self
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Fluent setter for isMame flag.
     * Indicates if this field is the "name" of the entity. For example, if BLog has title field, then title is the name.
     * This is used in various places, like in the admin panel, make links only on name fields, etc.
     */
    public function main(?bool $main = null): self
    {
        $this->main = $main ?? true;
        return $this;
    }

    /**
     * Return true if field is main.
     */
    public function isMain(): bool
    {
        // Check if the field name is 'name' or 'title', which are common conventions for name fields
        return $this->main
            || in_array($this->name, ['name', 'title'], true);
    }

    /**
     * Fluent setter for min value.
     */
    public function min(int|float $min): self
    {
        $this->min = $min;
        $this->rules[] = 'min:' . $min;
        return $this;
    }

    /**
     * Fluent setter for max value.
     */
    public function max(int|float $max): self
    {
        $this->max = $max;
        $this->rules[] = 'max:' . $max;
        return $this;
    }

    /**
     * Fluent setter for stp value (precision).
     */
    public function step(float $step): self
    {
        $this->step = $step;
        return $this;
    }

    /**
     * Fluent setter for sortable flag.
     */
    public function sortable(): static
    {
        $this->sortable = true;
        return $this;
    }

    /**
     * Fluent setter for searchable flag.
     */
    public function searchable(): static
    {
        $this->searchable = true;
        return $this;
    }

    /**
     * Fluent setter for unique flag.
     */
    public function unique(?string $table = null, ?string $column = null): static
    {
        $rule = 'unique';
        if ($table && $column) {
            $rule .= ":{$table},{$column}";
        }
        $this->rules[] = $rule;
        $this->unique = true;

        return $this;
    }

    /**
     * Fluent setter for readonly flag.
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Fluent setter for hidden flag.
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Fluent setter for label.
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Fluent setter for comment.
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Fluent setter for showInTable flag.
     */
    public function showInTable(bool $showInTable = true): self
    {
        $this->showInTable = $showInTable;
        return $this;
    }

    /**
     * Fluent setter for showInForm flag.
     */
    public function showInForm(bool $showInForm = true): self
    {
        $this->showInForm = $showInForm;
        return $this;
    }

    /**
     * Database table if the field is a foreign key.
     */
    public function table(?string $table): self
    {
        $this->table = $table;
        return $this;
    }
}
