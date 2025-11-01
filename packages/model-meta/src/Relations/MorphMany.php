<?php

namespace Glugox\ModelMeta\Relations;

use Glugox\ModelMeta\Relation;
use Glugox\ModelMeta\RelationType;

class MorphMany extends Relation {
    /**
     * Constructor
     */
    public function __construct(string $name)
    {
        parent::__construct($name, RelationType::MORPH_MANY);
    }

    public static function make(string $name): self
    {
        return new self($name);
    }
}