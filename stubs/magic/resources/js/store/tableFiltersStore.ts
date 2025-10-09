import {reactive, watch} from "vue"
import type {DataTableFilters, FilterValue, TableId} from "@/types/support"
import {isEmptyFilterValue, isEqual} from "@/lib/app";

// Reactive store holding filters per table
const filtersStore = reactive<Record<TableId, DataTableFilters>>({})

// We want to provide only one watcher per table for subscriptions
const filterWatchers: Record<TableId, (() => void) | undefined> = {}

/**
 * Get or create the filters state for a table.
 * Shared across components, reactive.
 */
export function useFilters(tableId: TableId): DataTableFilters {
    if (!filtersStore[tableId]) {
        // Create a new reactive object for this table
        filtersStore[tableId] = reactive<DataTableFilters>({})
    }
    return filtersStore[tableId]
}

/**
 * Optional: reset filters for a specific table
 */
export function resetFilters(tableId: TableId) {
    if (filtersStore[tableId]) {
        Object.keys(filtersStore[tableId]).forEach(
            key => delete filtersStore[tableId][key]
        )
    }
}

/**
 * Handle a filter change event.
 * Updates the filters store for the given table and field.
 * Removes the filter if the value is empty.
 */
export const setFilterValue = (tableId: string, field: string, value: FilterValue) => {
    console.log("Setting filter", {tableId, field, value})
    if (filtersStore[tableId]) {
        if (!isEqual(filtersStore[tableId][field], value)) {
            if (isEmptyFilterValue(value)) {
                filtersStore[tableId][field] = null
                delete filtersStore[tableId][field]
            } else {
                filtersStore[tableId][field] = value
            }
        }
    }
}

/**
 * Subscribe to changes in a table's filters.
 * Callback is invoked whenever filters change.
 * Returns an unsubscribe function.
 */
export function subscribeToFilters(
    tableId: TableId,
    callback: (filters: DataTableFilters) => void
) {

    if (filterWatchers[tableId]) {
        return filterWatchers[tableId]!
    }

    const filters = useFilters(tableId)
    let previousJson = JSON.stringify(filters)

    const listener = watch(filters,
        async (newVal) => {
            const currentJson = JSON.stringify(newVal)
            if (currentJson !== previousJson) {
                previousJson = currentJson
                callback(newVal)
            }
        },
        { deep: true, immediate: true }
    )// caller can invoke this to unsubscribe

    filterWatchers[tableId] = listener
    return listener
}
