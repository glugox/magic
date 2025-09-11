<?php

namespace App\Traits;

/**
 * Trait HasName
 *
 * Provides a dynamic way to get a "name" attribute for Eloquent models.
 * The name can be derived from specified fields or a custom method.
 *
 * Usage:
 * - Define a protected property `$nameFields` as an array of field names
 *   to concatenate for the name, e.g. `protected $nameFields = ['first_name', 'last_name'];`
 * - Alternatively, implement a `resolveName()` method in the model to return
 *   a custom name string.
 *
 * If neither is provided, it defaults to "ModelClass #ID".
 *
 * @phpstan-ignore trait.unused
 */
trait HasName
{
    /**
     * Get the name attribute for the model.
     *
     * This method checks if a specific name field is defined,
     * or if a custom resolveName method exists, and returns
     * the appropriate name. If neither is available, it defaults
     * to a class name with the model ID.
     */
    public function getNameAttribute(): string
    {
        // Check if the model has a specific name field defined
        // and return its value if it exists.
        // For example:
        // protected $nameField = 'orderNumber';
        if (property_exists($this, 'nameFields')) {
            $nameFields = (array) $this->nameFields;
            $nameParts = [];
            foreach ($nameFields as $field) {
                // We must exclude 'name' to avoid recursion
                if ($field === 'name') {
                    continue;
                }
                if (isset($this->{$field})) {
                    $nameParts[] = $this->{$field};
                }
            }
            if ($nameParts !== []) {
                return implode(' ', $nameParts);
            }

            return 'N/A';
        }

        // If a custom resolveName method exists, call it to get the name.
        // This allows for dynamic name resolution based on model logic.
        // For example:
        // public function resolveName(): string
        // {
        //     return $this->order_number . ' - ' . $this->status;
        // }
        if (method_exists($this, 'resolveName')) {
            return $this->resolveName();
        }

        // If no specific name field or custom method is defined,
        // return a default name format using the class name and model ID.
        // For example: "Order #123"
        return class_basename($this)." #{$this->id}";
    }
}
