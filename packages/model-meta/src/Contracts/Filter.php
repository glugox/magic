<?php

namespace Glugox\ModelMeta\Contracts;

use Glugox\ModelMeta\Requests\MetaRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface Filter {

    /**
     * Apply the filter to the query.
     *
     * @param Builder<Model> $query
     * @param mixed $values
     * @return Builder<Model>
     */
    public function apply(Builder $query, mixed $values): Builder;

    /**
     * Return the unique key for this filter.
     */
    public function key(): string;

    /**
     * Human-readable label for UI.
     */
    public function label(string $val): static;

    /**
     * Optional: return available options (for select filters).
     */
    //public function options(): array;
}
