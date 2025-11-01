<?php

namespace Glugox\ModelMeta\Filters;

use Glugox\ModelMeta\FilterType;
use Glugox\ModelMeta\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NumberFilter extends BaseFilter implements Filter {

    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::NUMBER);
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
     * @param string $values
     * @return Builder<Model>
     */
    public function apply(Builder $query, mixed $values): Builder
    {
        return $query;
    }

    public function key(): string
    {
        return 'number_'.$this->column;
    }
}