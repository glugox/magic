<?php

namespace Glugox\ModelMeta\Relations;

use Glugox\ModelMeta\Relation;
use Glugox\ModelMeta\RelationType;

class BelongsToMany extends Relation {
    /**
     * Constructor
     */
    public function __construct(string $name)
    {
        parent::__construct($name, RelationType::BELONGS_TO_MANY);
    }

    public static function make(string $name): self
    {
        return new self($name);
    }
}