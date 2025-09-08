import {computed, ref, toRefs, watch} from "vue"
import {router} from "@inertiajs/vue3"
import {getCoreRowModel, RowSelectionState, SortingState, useVueTable} from "@tanstack/vue-table"
import type {Controller, DbId, PaginatedResponse, TableFilters} from "@/types/support"
import {arraysEqualIgnoreOrder, debounced} from "@/lib/app";
import axios from "axios";

export function useResourceTable<T>(
    props: {
        data: PaginatedResponse<T>,
        filters: TableFilters,
        controller: Controller,
        parentId?: DbId,
        columns: any[]
    }
) {
    const { data, filters, controller, parentId, columns } = toRefs(props)

    const rows = ref<T[]>(data.value.data as T[])
    const page = ref(data.value.meta.current_page)
    const perPage = ref(data.value.meta.per_page)
    const total = ref(data.value.meta.total)
    const lastPage = ref(data.value.meta.last_page)

    const sorting = ref<SortingState>(
        filters.value?.sortKey ? [{ id: filters.value.sortKey, desc: filters.value.sortDir === "desc" }] : []
    )
    const sortKey = ref(filters.value?.sortKey ?? null)
    const sortDir = ref(filters.value?.sortDir ?? null)
    const search = ref(filters.value?.search ?? "")

    const rowSelection = ref<RowSelectionState>(
        mapIdsToRowSelection(filters.value?.selectedIds ?? [], rows.value as T[])
    )

    // Ids of currently selected rows ( from filters )
    const selectedIds = ref<DbId[]>(filters.value?.selectedIds ?? [])
    const lastSavedIds = ref<DbId[]>(filters.value?.selectedIds ?? [])

    /**
     * Watch for changes in rows or selectedIds to update rowSelection ( actual visible selection in the table )
     */
    watch(
        [() => rows.value, () => filters.value.selectedIds],
        ([newRows, ids]) => {
            rowSelection.value = mapIdsToRowSelection(ids ?? [], newRows as T[])
        },
        { immediate: true }
    )

    /**
     * Watch for changes in rowSelection to update selectedIds (ids of selected rows)
     */
    watch(rowSelection, (selection) => {
        selectedIds.value = Object.keys(selection)
            .filter(key => selection[+key])  // only `true` entries
            .map(key => (rows.value[+key] as any).id as DbId)

        if (controller.value.updateSelection !== undefined) {
            // If selectedIds differ from lastSavedIds, save the diff
            if (!arraysEqualIgnoreOrder(selectedIds.value, lastSavedIds.value)) {
                debounced(saveSelection, 500)()
            }
        }

    })

    /**
     * Send request to server with current filters
     */
    const send = () => {
        const params: any = {
            page: page.value,
            perPage: perPage.value,
            search: search.value,
        }
        if (sortKey.value) params.sortKey = sortKey.value
        if (sortDir.value) params.sortDir = sortDir.value

        router.get(controller.value.index(parentId?.value), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }

    /**
     * Map array of IDs to RowSelectionState
     * RowSelectionState is an object where keys are row indices and values are booleans indicating selection
     * e.g. { 0: true, 2: true } means rows at index 0 and 2 are selected
     * This function finds the indices of the rows with the given IDs and marks them as selected
     *
     * @param ids
     * @param rows
     */
    function mapIdsToRowSelection(ids: DbId[], rows: T[]): RowSelectionState {
        const selection: RowSelectionState = {}
        ids.forEach(id => {
            const index = rows.findIndex(r => (r as any).id === id)
            if (index !== -1) {
                selection[index] = true
            }
        })
        return selection
    }

    /**
     * Initialize TanStack Table instance
     */
    const table = useVueTable({
        data: rows.value,
        columns: columns.value,
        state: {
            get sorting() {
                return sorting.value
            },
            set sorting(updater) {
                sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
            },
            get rowSelection() {
                return rowSelection.value
            }
        },
        onSortingChange: (updater) => {
            sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
            const sort = sorting.value[0]
            sortKey.value = sort?.id ?? null
            sortDir.value = sort ? (sort.desc ? "desc" : "asc") : null
            page.value = 1
            send()
        },
        enableRowSelection: true,
        onRowSelectionChange: updateOrValue => {
            rowSelection.value =
                typeof updateOrValue === 'function'
                    ? updateOrValue(rowSelection.value)
                    : updateOrValue
        },
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
    })

    /**
     * Save only changes (diff) to server
     */
    const saveSelection = async () => {
        try {
            const added = selectedIds.value.filter(id => !lastSavedIds.value.includes(id))
            const removed = lastSavedIds.value.filter(id => !selectedIds.value.includes(id))

            if (added.length === 0 && removed.length === 0) {
                return // nothing changed
            }

            const url = controller.value.updateSelection(parentId?.value).url
            console.log('Saving selection diff:', { added, removed })

            const response = await axios.post(url, { added, removed })
            console.log('Selection saved:', response.data)

            // Update lastSavedIds with confirmed server state
            lastSavedIds.value = [...response.data.selectedIds]
            filters.value.selectedIds = [...response.data.selectedIds]

        } catch (err) {
            console.error('Failed to save selection', err)
        }
    }

    // sync inertia updates
    watch(
        data,
        (newData) => {
            rows.value = newData.data
            page.value = newData.meta.current_page
            perPage.value = newData.meta.per_page
            total.value = newData.meta.total
            lastPage.value = newData.meta.last_page
            table.setOptions(prev => ({ ...prev, data: rows.value }))
        }
    )

    // auto-send when page/perPage/search change
    watch([page, perPage, search], send)

    return { table, rows, page, perPage, total, lastPage, search, sorting, selectedIds, send }
}
