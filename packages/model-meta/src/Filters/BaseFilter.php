<?php

namespace Glugox\ModelMeta\Filters;

use Glugox\ModelMeta\Filter;
use Glugox\ModelMeta\FilterType;

class BaseFilter extends Filter {

    public function __construct(
        string $name,
        FilterType $type,
        ?string $label = null
    ) {
        parent::__construct($name, $type, $label);
    }

    /**
     * Set the label for the filter.
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }


}