<?php

namespace Glugox\ModelMeta\Filters;

use Glugox\ModelMeta\FilterType;
use Glugox\ModelMeta\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DateFilter extends BaseFilter implements Filter {

    /**
     * Create a new filter instance.
     */
    public function __construct(
        protected string $column
    ) {
        parent::__construct($column, FilterType::DATE_RANGE);
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
            /** @var string $min*/
            $min= $values['min'] ?? null;
            /** @var string $max */
            $max = $values['max'] ?? null;
            if ($min && $max && $min < $max) {
                return $query->whereBetween($this->column, [Carbon::parse($min), Carbon::parse($max)]);
            } elseif (isset($values['min'])) {
                return $query->whereDate($this->column, '>=', Carbon::parse($min));
            } elseif (isset($values['max'])) {
                return $query->whereDate($this->column, '<=', Carbon::parse($max));
            }
        } else {
            return $query->whereDate($this->column, '=', $values);
        }
        return $query;
    }

    public function key(): string
    {
        return 'date_'.$this->column;
    }
}