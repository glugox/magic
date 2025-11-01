<?php

namespace Glugox\ModelMeta;

abstract class Relation
{
    public function __construct(
        public string $name,
        public RelationType $type,
        private ?string $relatedEntityName = null,
        private ?string $relatedKey = null,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null,
        protected ?string $relationName = null,
        protected ?string $morphName = null,
        /** @var string[] */
        protected ?string $eagerFields = null,
        public bool $showInTable = false,
    ) {}

    /**
     * Fluent setter for showInTable flag.
     */
    public function showInTable(bool $showInTable = true): self
    {
        $this->showInTable = $showInTable;
        return $this;
    }

    public function getRelatedEntityName(): ?string
    {
        return $this->relatedEntityName;
    }
    public function relatedEntityName(?string $relatedEntityName): self
    {
        $this->relatedEntityName = $relatedEntityName;
        return $this;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }
    public function foreignKey(?string $foreignKey): self
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function getRelationName(): string
    {
        return $this->relationName ?? $this->name;
    }

    public function getRelatedKey(): ?string
    {
        return $this->relatedKey;
    }
    public function relatedKey(?string $relatedKey): self
    {
        $this->relatedKey = $relatedKey;
        return $this;
    }

    public function getMorphName(): ?string
    {
        return $this->morphName;
    }
    public function morphName(?string $morphName): self
    {
        $this->morphName = $morphName;
        return $this;
    }

    /**
     * Eager fields to load on the related model.
     *
     * @return string|null
     */
    public function getEagerFields(): ?string
    {
        return $this->eagerFields;
    }

    /**
     * @param string|null $eagerFields
     * @return $this
     */
    public function eagerFields(?string $eagerFields): self
    {
        $this->eagerFields = $eagerFields;
        return $this;
    }

    public function isPolymorphic(): bool
    {
        return in_array($this->type, [
            RelationType::MORPH_ONE,
            RelationType::MORPH_MANY,
            RelationType::MORPH_TO,
            RelationType::MORPH_TO_MANY,
            RelationType::MORPHED_BY_MANY,
        ]);
    }

    public function getMorphTypeKey(): ?string
    {
        return $this->isPolymorphic() && $this->morphName ? $this->morphName.'_type' : null;
    }

    public function getApiPath(): string
    {
        return strtolower($this->name).'s';
    }

    public function getRouteName(): string
    {
        return strtolower($this->name).'.index';
    }
}