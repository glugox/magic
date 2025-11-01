<?php

namespace Glugox\ModelMeta;

abstract class Filter
{
    public function __construct(
        public string $name,
        public FilterType $type,
        public ?string $label = null,
    ) {}
}