<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TableQueryService
{
    /**
     * Constructor
     */
    public function __construct(
        protected SearchService $searchService,
    ) {}

    /**
     * Apply all query modifications: search, sort, pagination.
     *
     * Usage:
     * ```php
     * $query = Product::query();
     * $query = $tableQueryService->applyAll(
     *     $query,
     *     searchableFields: ['name', 'description'],
     *     selectedIds: $selectedProductIds,
     *     defaultSortField: 'name',
     *     defaultSortDir: 'asc',
     *     relations: ['category', 'tags'],
     *     queryFields: ['id', 'name', 'price', 'category_id']
     * );
     * $items = $query->paginate(12);
     * ```
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param array $searchableFields Fields to search in.
     * @param string[] $relations Relations to eager load.
     * @param array $selectFields Fields to select in the query.
     * @param string|null $searchString The search string from request.
     * @param string $defaultSortField Default field to sort by if none specified in request.
     * @param string $defaultSortDir Default sort direction ('asc' or 'desc').
     *
     * @return Builder The modified query builder instance.
     */
    public function applyAll(
        Builder $query,
        array $searchableFields = [],
        ?array $relations = [],
        array $selectFields = [],
        ?string $searchString = '',
        string $defaultSortField = 'id',
        string $defaultSortDir = 'desc',
    ): Builder {

        // Eager load relations
        if ($relations !== null && $relations !== []) {
            $query->with($relations);
        }

        // Select specific fields if any
        if ($selectFields !== []) {
            $query->select($selectFields);
        }

        // Apply search
        if ($searchString !== null && $searchString !== '' && $searchString !== '0') {
            $query = $this->searchService->apply(
                $query,
                $searchString,
                $searchableFields
            );
        }

        // Apply sorting
        return $this->applySort(
            $query,
            $defaultSortField,
            $defaultSortDir
        );
    }

    /**
     * Apply sorting to a query.
     *
     * If `sortKey` exists in request, uses it.
     * Otherwise, can optionally order `selectedIds` first, then by default field and direction.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param string $defaultField Default field to sort by if none specified in request.
     * @param string $defaultDir Default sort direction ('asc' or 'desc').
     * @return Builder The modified query builder instance.
     */
    public function applySort(
        Builder $query,
        string $defaultField = 'id',
        string $defaultDir = 'desc'
    ): Builder {
        $request = request();

        if ($request->has('sortKey')) {
            $query->orderBy($request->get('sortKey', 'id'), $request->get('sortDir', 'asc'));
        }/* elseif (!empty($selectedIds)) {
            // Selected first
            $ids = implode(',', $selectedIds);
            $query->orderByRaw("CASE WHEN id IN ({$ids}) THEN 0 ELSE 1 END")
                ->orderBy($defaultField, $defaultDir);
        }*/ else {
            $query->orderBy($defaultField, $defaultDir);
        }

        return $query;
    }

    /**
     * Apply pagination to a query.
     */
    public function paginate(Builder $query, int $page = 1, int $perPage = 12): LengthAwarePaginator
    {
        // Paginate
        return $query->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );
    }
}
