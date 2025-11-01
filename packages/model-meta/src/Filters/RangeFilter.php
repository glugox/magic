<?php

namespace Glugox\ModelMeta\Filters;


use Glugox\ModelMeta\Contracts\Filter;
use Glugox\ModelMeta\FilterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class RangeFilter extends BaseFilter implements Filter
{
    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::RANGE);
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
        if (is_array($values)) {
            /** @var string $min */
            $min = $values['min'] ?? null;
            /** @var string $max */
            $max = $values['max'] ?? null;
            if ($min && $max && $min < $max) {
                return $query->whereBetween($this->column, [$min, $max]);
            } elseif ($min) {
                return $query->where($this->column, '>=', $min);
            } elseif ($max) {
                return $query->where($this->column, '<=',$max);
            }
        } else {
            return $query->where($this->column, '=', $values);
        }
        return $query;
    }

    /**
     * Get the key for the filter.
     */
    public function key(): string
    {
        return 'range_'.$this->column;
    }
}