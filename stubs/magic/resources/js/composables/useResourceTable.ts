import { computed, ref, toRefs, watch } from "vue"
import { router } from "@inertiajs/vue3"
import {
    getCoreRowModel,
    RowSelectionState,
    SortingState,
    useVueTable,
} from "@tanstack/vue-table"
import type {
    Controller,
    DbId,
    PaginatedResponse,
    TableFilters,
} from "@/types/support"
import { arraysEqualIgnoreOrder, debounced } from "@/lib/app"
import axios from "axios"

export function useResourceTable<T>(props: {
    data: PaginatedResponse<T>
    filters: TableFilters
    controller: Controller
    parentId?: DbId
    columns: any[]
}) {
    const { data, filters, controller, parentId, columns } = toRefs(props)

    const rows = ref<T[]>(data.value.data as T[])
    const page = ref(data.value.meta.current_page)
    const perPage = ref(data.value.meta.per_page)
    const total = ref(data.value.meta.total)
    const lastPage = ref(data.value.meta.last_page)

    const sorting = ref<SortingState>(
        filters.value?.sortKey
            ? [
                {
                    id: filters.value.sortKey,
                    desc: filters.value.sortDir === "desc",
                },
            ]
            : []
    )
    const sortKey = ref(filters.value?.sortKey ?? null)
    const sortDir = ref(filters.value?.sortDir ?? null)
    const search = ref(filters.value?.search ?? "")

    // --- global truth ---
    const selectedIds = ref<DbId[]>(filters.value?.selectedIds ?? [])
    const lastSavedIds = ref<DbId[]>(filters.value?.selectedIds ?? [])

    // 🔑 Keep local state in sync with Inertia-provided filters
    watch(
        () => filters.value.selectedIds,
        (ids) => {
            if (ids) {
                selectedIds.value = [...ids]
                lastSavedIds.value = [...ids]
            }
        },
        { immediate: true }
    )

    // --- current page selection (mutable for table) ---
    const rowSelection = ref<RowSelectionState>(mapIdsToRowSelection(selectedIds.value, rows.value as T[]))

    watch([rows, selectedIds], () => {
        rowSelection.value = mapIdsToRowSelection(selectedIds.value, rows.value as T[])
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
     */
    function mapIdsToRowSelection(
        ids: DbId[],
        rows: T[]
    ): RowSelectionState {
        const selection: RowSelectionState = {}
        ids.forEach((id) => {
            const index = rows.findIndex((r) => (r as any).id === id)
            if (index !== -1) {
                selection[index] = true
            }
        })
        return selection
    }

    /**
     * Handle user selecting/unselecting rows in the current page
     */
    function toggleRowSelection(
        updater:
            | RowSelectionState
            | ((old: RowSelectionState) => RowSelectionState)
    ) {
        const updated = typeof updater === "function" ? updater(rowSelection.value) : updater
        rowSelection.value = { ...updated } // ← important for Vue reactivity

        // Take current selection for this page
        const pageSelectedIds = Object.keys(updated)
            .filter((key) => updated[+key])
            .map((key) => (rows.value[+key] as any).id as DbId)

        // Merge into global selectedIds
        const currentPageIds = rows.value.map(
            (r) => (r as any).id as DbId
        )

        // 🔑 preserve previously selected items from *other pages*
        selectedIds.value = [
            // keep global selections not on this page
            ...selectedIds.value.filter(
                (id) => !currentPageIds.includes(id)
            ),
            // add this page’s selected items
            ...pageSelectedIds,
        ]

        // Trigger save if needed
        if (controller.value.updateSelection !== undefined) {
            if (
                !arraysEqualIgnoreOrder(
                    selectedIds.value,
                    lastSavedIds.value
                )
            ) {
                debouncedSaveSelection()
            }
        }
    }

    /**
     * Save only changes (diff) to server
     */
    const saveSelection = async () => {
        try {
            const added = selectedIds.value.filter(
                (id) => !lastSavedIds.value.includes(id)
            )
            const removed = lastSavedIds.value.filter(
                (id) => !selectedIds.value.includes(id)
            )

            if (added.length === 0 && removed.length === 0) {
                return // nothing changed
            }

            const url = controller.value.updateSelection(parentId?.value).url
            console.log("Saving selection diff:", { added, removed })

            const response = await axios.post(url, { added, removed })
            console.log("Selection saved:", response.data)

            // Update lastSavedIds with confirmed server state
            lastSavedIds.value = [...response.data.selectedIds]
            filters.value.selectedIds = [...response.data.selectedIds]
        } catch (err) {
            console.error("Failed to save selection", err)
        }
    }

    const debouncedSaveSelection = debounced(saveSelection, 500)

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
                sorting.value =
                    typeof updater === "function"
                        ? updater(sorting.value)
                        : updater
            },
            get rowSelection() {
                return rowSelection.value
            },
            set rowSelection(updater) {
                toggleRowSelection(updater) // update global selectedIds
            },
        },
        enableRowSelection: true,
        onSortingChange: (updater) => {
            sorting.value =
                typeof updater === "function"
                    ? updater(sorting.value)
                    : updater
            // keep filters in sync
            if (sorting.value.length > 0) {
                sortKey.value = sorting.value[0].id
                sortDir.value = sorting.value[0].desc ? "desc" : "asc"
            } else {
                sortKey.value = null
                sortDir.value = null
            }
        },
        onRowSelectionChange: toggleRowSelection, // 🔑 ← THIS WAS MISSING
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
    })

    // sync inertia updates
    watch(data, (newData) => {
        rows.value = newData.data
        page.value = newData.meta.current_page
        perPage.value = newData.meta.per_page
        total.value = newData.meta.total
        lastPage.value = newData.meta.last_page
        table.setOptions((prev) => ({ ...prev, data: rows.value }))
    })

    // auto-send when page/perPage/search change
    watch([page, perPage, search, sortKey, sortDir], send)

    return {
        table,
        rows,
        page,
        perPage,
        total,
        lastPage,
        search,
        sorting,
        selectedIds,
        send,
    }
}
