<?php

namespace Glugox\ModelMeta\Filters;


use Glugox\ModelMeta\Contracts\Filter;
use Glugox\ModelMeta\FilterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class BelongsToManyFilter extends BaseFilter implements Filter
{
    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::BELONGS_TO_MANY);
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
        if (empty($values)) {
            return $query;
        }

        // Normalize values to array of IDs
        $ids = is_array($values) ? $values : [$values];

        // Use whereHas on the relation (the relation name should match the column)
        return $query->whereHas($this->column, function (Builder $q) use ($ids) {
            $q->whereIn("{$q->getModel()->getTable()}.id", $ids);
        });
    }


    /**
     * Get the key for the filter.
     */
    public function key(): string
    {
        return 'belongs_to_many'.$this->column;
    }
}