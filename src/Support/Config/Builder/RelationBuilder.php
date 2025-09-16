<?php

namespace Glugox\Magic\Support\Config\Builder;

use Glugox\Magic\Support\Config\Entity;
use Glugox\Magic\Support\Config\Relation;
use Glugox\Magic\Support\Config\RelationType;

class RelationBuilder
{
    private RelationType $type;

    private Entity $localEntity;

    private ?string $relatedEntityName = null;

    private ?Entity $relatedEntity = null;

    private ?string $foreignKey = null;

    private ?string $localKey = null;

    private ?string $relatedKey = null;

    private ?string $relationName = null;

    private ?string $morphName = null;

    private ?string $pivotTable = null;

    public function type(RelationType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function localEntity(Entity $entity): self
    {
        $this->localEntity = $entity;

        return $this;
    }

    public function relatedEntityName(string $name): self
    {
        $this->relatedEntityName = $name;

        return $this;
    }

    public function relatedEntity(Entity $entity): self
    {
        $this->relatedEntity = $entity;

        return $this;
    }

    public function foreignKey(string $key): self
    {
        $this->foreignKey = $key;

        return $this;
    }

    public function localKey(string $key): self
    {
        $this->localKey = $key;

        return $this;
    }

    public function relatedKey(string $key): self
    {
        $this->relatedKey = $key;

        return $this;
    }

    public function relationName(string $name): self
    {
        $this->relationName = $name;

        return $this;
    }

    public function morphName(string $name): self
    {
        $this->morphName = $name;

        return $this;
    }

    public function pivotTable(string $table): self
    {
        $this->pivotTable = $table;

        return $this;
    }

    /**
     * Build and return the Relation object.
     */
    public function build(): Relation
    {
        return new Relation(
            type: $this->type,
            localEntity: $this->localEntity,
            relatedEntityName: $this->relatedEntityName,
            relatedEntity: $this->relatedEntity,
            foreignKey: $this->foreignKey,
            localKey: $this->localKey,
            relatedKey: $this->relatedKey,
            relationName: $this->relationName,
            morphName: $this->morphName,
            pivotTable: $this->pivotTable,
        );
    }
}
