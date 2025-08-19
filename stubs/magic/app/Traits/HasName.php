<?php

namespace App\Traits;

trait HasName
{
    /**
     * Get the name attribute for the model.
     *
     * This method checks if a specific name field is defined,
     * or if a custom resolveName method exists, and returns
     * the appropriate name. If neither is available, it defaults
     * to a class name with the model ID.
     *
     */
    public function getNameAttribute(): string
    {
        // Check if the model has a specific name field defined
        // and return its value if it exists.
        // For example:
        // protected $nameField = 'orderNumber';
        if (property_exists($this, 'nameField') && $this->{$this->nameField}) {
            return $this->{$this->nameField};
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
        return class_basename($this) . " #{$this->id}";
    }
}
