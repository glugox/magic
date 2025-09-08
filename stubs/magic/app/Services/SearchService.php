<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Apply search to a query builder.
     *
     * @param Builder $query
     * @param string|null $searchTerm
     * @param array $searchableFields
     * @param array $relations
     * @return Builder
     */
    public function apply(
        Builder $query,
        ?string $searchTerm,
        ?array $searchableFields = [],
    ): Builder {

        // Apply search
        if ($searchTerm && !empty($searchableFields)) {
            $query->where(function ($q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }
}
