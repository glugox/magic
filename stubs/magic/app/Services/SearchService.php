<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Apply search to a query builder.
     *
     * @param  array  $relations
     */
    public function apply(
        Builder $query,
        ?string $searchTerm,
        ?array $searchableFields = [],
    ): Builder {

        // Apply search
        if ($searchTerm && ($searchableFields !== null && $searchableFields !== [])) {
            $query->where(function ($q) use ($searchTerm, $searchableFields): void {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }
}
