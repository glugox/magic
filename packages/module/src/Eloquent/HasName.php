<?php

namespace Glugox\Module\Eloquent;

trait HasName
{
    public function getNameAttribute(): string
    {
        if (property_exists($this, 'nameFields')) {
            $nameFields = (array) $this->nameFields;
            $nameParts = [];

            foreach ($nameFields as $field) {
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

        if (method_exists($this, 'resolveName')) {
            return (string) $this->resolveName();
        }

        return class_basename(static::class).' #'.$this->getKey();
    }
}
