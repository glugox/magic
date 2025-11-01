<?php

namespace Glugox\ModelMeta\Filters;


use Glugox\ModelMeta\Contracts\Filter;
use Glugox\ModelMeta\FilterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class BelongsToFilter extends BaseFilter implements Filter
{
    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::BELONGS_TO);
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
        if (is_array($values) && count($values) > 0) {
            $query->whereIn($this->column, $values);
        } elseif (!is_array($values) && !empty($values)) {
            $query->where($this->column, $values);
        }
        return $query;
    }


    /**
     * Get the key for the filter.
     */
    public function key(): string
    {
        return 'belongs_to'.$this->column;
    }
}