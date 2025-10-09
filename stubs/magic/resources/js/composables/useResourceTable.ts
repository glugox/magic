import {computed, ref, toRefs, watch, onUnmounted, unref, toRaw} from "vue"
import {router, useForm} from "@inertiajs/vue3"
import {getCoreRowModel, RowSelectionState, SortingState, useVueTable,} from "@tanstack/vue-table"
import type {
    DataTableFilters,
    DataTableSettings,
    DbId,
    ResourceData,
    ResourceTableProps,
    TableId,
} from "@/types/support"
import {arraysEqualIgnoreOrder, debounced, isEqual} from "@/lib/app"
import axios from "axios"
import {useEntityContext} from "@/composables/useEntityContext";
import {useFilters, subscribeToFilters} from "@/store/tableFiltersStore";

export function useResourceTable<T>(props: ResourceTableProps<T>, tableId: TableId) {

    const { data, parentId, columns } = toRefs(props)

    const settings = ref<DataTableSettings>(props.state?.settings ?? {})

    const filters = useFilters(tableId)

    // For toggling filters box visibility
    const filtersVisible = ref(false)
    function toggleFilters() {
        filtersVisible.value = !filtersVisible.value
    }

    // Entity context gives us Laravel style entity setup
    const {controller} = useEntityContext(props.entity, props.parentEntity, props.parentId)

    // Data table coming from Laravel pagination (PaginationObject)
    const rows = ref<T[]>(data.value.data as T[])
    const page = ref(data.value.meta.current_page)
    const perPage = ref(data.value.meta.per_page)
    const total = ref(data.value.meta.total)
    const lastPage = ref(data.value.meta.last_page)

    const sorting = ref<SortingState>(
        settings.value.sortKey
            ? [{id: settings.value.sortKey as string, desc: settings.value.sortDir === "desc"}]
            : []
    )
    const sortKey = ref(settings.value.sortKey ?? null)
    const sortDir = ref(settings.value.sortDir ?? null)

    // --- global truth ---
    const selectedIds = ref<DbId[]>(settings.value.selectedIds as DbId[] ?? [])
    const lastSavedIds = ref<DbId[]>(settings.value.selectedIds as DbId[] ?? [])

    // Processing state for displaying spinner during network requests
    const bulkActionProcessing = ref(false)

    // Remember last sent params to avoid duplicate requests
    const lastSentParams = ref<Record<string, any> | null>(null)

    // 🔑 Keep local state in sync with Inertia-provided filters
    watch(
        () => settings.value.selectedIds,
        (ids) => {
            if (ids) {
                selectedIds.value = [...ids]
                lastSavedIds.value = [...ids]
            }
        },
        { immediate: true }
    )

    // Current page selection (mutable for table)
    const rowSelection = ref<RowSelectionState>(mapIdsToRowSelection(selectedIds.value, rows.value as T[]))

    watch([rows, selectedIds], () => {
        rowSelection.value = mapIdsToRowSelection(selectedIds.value, rows.value as T[])
    })

    /**
     * Send request to server with current filters
     */
    const send = () => {
        const cleaned = Object.fromEntries(
            Object.entries(toRaw(filters))
                .filter(([_, v]) => v !== null && v !== "")
                .map(([k, v]) => [k, toRaw(v)])
        )

        const params: any = {
            page: page.value,
            perPage: perPage.value,
            ...cleaned
        }
        if (sortKey.value) params.sortKey = sortKey.value
        if (sortDir.value) params.sortDir = sortDir.value

        // Skip sending if params didn’t change
        if (lastSentParams.value && isEqual(lastSentParams.value, params)) {
            console.log("Skipped send — params unchanged", params)
            return
        }

        // If we have search term, and it is different from one in last sent params, reset to page 1
        if (cleaned.search && lastSentParams.value && cleaned.search !== lastSentParams.value.search) {
            console.log("Resetting to page 1 due to search change")
            page.value = 1
            params.page = 1
        }

        // Remember last params
        lastSentParams.value = JSON.parse(JSON.stringify(params))

        // Show loading state in UI
        bulkActionProcessing.value = true

        console.log("Sending request with params", params)
        router.get(controller.value.index(parentId?.value), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                bulkActionProcessing.value = false
            },
        })
    }

    const sendLater = () => {
        debouncedSend()
    }

    const resetPageAndSend = () => {
        page.value = 1
        sendLater()
    }

    /**
     * Send debounced request to server with current filters
     */
    const debouncedSend = debounced(function (){
        send()
    }, 300)

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

        // preserve previously selected items from *other pages*
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
     * Save only changes (diff) of related items to server
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

            // Show loader in UI
            bulkActionProcessing.value = true

            // Send changes to server
            const response = await axios.post(url, { added, removed })

            // Hide loader in UI
            bulkActionProcessing.value = false

            // Update lastSavedIds with confirmed server state
            lastSavedIds.value = [...response.data.selectedIds]
        } catch (err) {
            console.error("Failed to save selection", err)
        }
    }

    const debouncedSaveSelection = debounced(saveSelection, 500)

    /**
     * Initialize TanStack Table instance
     */
    const table = useVueTable({
        data: rows.value as unknown as ResourceData[],
        columns: columns.value,
        state: {
            get sorting() {
                return sorting.value
            },
            set sorting(updater) {
                sorting.value = updater
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
        onRowSelectionChange: toggleRowSelection,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
    })

    /**
     * Perform bulk action on selected rows
     */
    async function performBulkAction(action: "edit" | "delete" | "archive") {
        if (selectedIds.value.length === 0) {
            alert("No items selected.")
            return
        }

        /*const confirmed =
            action === "delete"
                ? confirm(`Are you sure you want to delete ${selectedIds.value.length} item(s)?`)
                : confirm(`Archive ${selectedIds.value.length} item(s)?`)

        if (!confirmed) return*/

        try {
            bulkActionProcessing.value = true // show spinner

            switch (action) {
                case "delete": {
                    // Use Wayfinder-generated form
                    const form = useForm({ ids: selectedIds.value })
                    const postForm = controller.value.bulkDestroy(parentId?.value)
                    await new Promise<void>((resolve, reject) => {
                        form.submit(postForm, {
                            onSuccess: () => resolve(),
                            onError: (err) => reject(err),
                        })
                    })

                    // clear selection after success
                    selectedIds.value = []
                    lastSavedIds.value = []

                    break
                }
                case "archive": {
                    const form = useForm({ ids: selectedIds.value })
                    const postForm = controller.value.bulkArchiveForm?.(parentId?.value)
                    if (postForm) {
                        await new Promise<void>((resolve, reject) => {
                            form.submit(postForm, {
                                onSuccess: () => resolve(),
                                onError: (err) => reject(err),
                            })
                        })
                    }
                    break
                }
                case "edit":
                    // optionally handle bulk edit here
                    break
            }
        } catch (err) {
            console.error(`Failed to ${action}`, err)
        } finally {
            bulkActionProcessing.value = false // hide spinner
        }
    }

    // Subscribe to external filter changes
    const filtersListener = subscribeToFilters(tableId, (f) => {
        sendLater()
    })

    onUnmounted(() => filtersListener())

    // sync inertia updates
    watch(data, (newData) => {
        rows.value = newData.data
        page.value = newData.meta.current_page
        perPage.value = newData.meta.per_page
        total.value = newData.meta.total
        lastPage.value = newData.meta.last_page
        table.setOptions((prev) => ({ ...prev, data: rows.value } as typeof prev))
    })

    // auto-send when page/perPage/sort change
    watch([page, perPage, sortKey, sortDir], () => sendLater())

    return {
        table,
        rows,
        page,
        perPage,
        total,
        lastPage,
        sorting,
        selectedIds,
        performBulkAction,
        bulkActionProcessing,
        filtersVisible,
        toggleFilters,
    }
}
