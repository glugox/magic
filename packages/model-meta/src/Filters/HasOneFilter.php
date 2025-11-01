<?php

namespace Glugox\ModelMeta\Filters;


use Glugox\ModelMeta\Contracts\Filter;
use Glugox\ModelMeta\FilterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class HasOneFilter extends BaseFilter implements Filter
{
    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::HAS_ONE);
    }

    /**
     * Make a new instance of the filter.
     */
    public static function make(string $column): self
    {
        return new self($column);
    }

    /**
     * Apply the filter to the given query.
     *
     * @param Builder<Model> $query
     * @param \DateTime|array{
     *     min?: string,
     *     max?: string
     * } $values
     * @return Builder<Model>
     */
    public function apply(Builder $query, mixed $values): Builder
    {
        // TODO: Implement the logic to apply the belongsToMany filter based on the provided values.
        return $query;
    }

    /**
     * Get the key for the filter.
     */
    public function key(): string
    {
        return 'has_one_'.$this->column;
    }
}