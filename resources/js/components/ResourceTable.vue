<script setup lang="ts">
import { ref, watch, computed } from "vue"
import { router } from "@inertiajs/vue3"
import { getCoreRowModel, useVueTable, SortingState, FlexRender } from "@tanstack/vue-table"

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/magic'

interface PaginationObject {
    data: any[]
    total: number
    current_page: number
    per_page: number
    search?: string
    sort_key?: string
    last_page?: number
    sort_dir?: "asc" | "desc"
    prev_page?: number
    next_page?: number
    prev_page_url?: string | null
    next_page_url?: string | null
    [key: string]: any
}


const { data, columns, filters, entityMeta } = defineProps<{
    data: PaginationObject,
    columns ?: any[],
    filters?: TableFilters,
    entityMeta?: Entity
}>()

const rows = ref(data.data)
const page = ref(data.current_page)
const perPage = ref(data.per_page)
const total = ref(data.total)
const search = ref(data.search ?? "")
const lastPage = ref(data.last_page ?? 1)
const prevPageUrl = ref(data.prev_page_url ?? null)
const nextPageUrl = ref(data.next_page_url ?? null)
const sorting = ref<SortingState>(filters.sortKey
    ? [{ id: filters.sortKey, desc: filters.sortDir === "desc" }]
    : []
)
const sortKey = ref(filters.sortKey ?? null)
const sortDir = ref(filters.sortDir ?? null)

// Debounce function to limit the frequency of API calls
// TODO: Move to a utility file
const debounced = (fn: Function, ms = 400) => {
    let t: number | undefined
    return (...args: any[]) => {
        clearTimeout(t)
        // @ts-ignore
        t = setTimeout(() => fn(...args), ms)
    }
}

const gotoPrevPage = () => {
    // set page and call send
    if (prevPageUrl.value) {
        page.value = prevPage.value
        send()
    }
}

const send = () => {
    console.log("Sending request with params:", {
        url: route(entityMeta.resourcePath),
        page: page.value,
        perPage: perPage.value,
        sortKey: sortKey.value,
        sortDir: sortDir.value,
        search: search.value
    })
    router.get(
        route(entityMeta.resourcePath),
        {
            page: page.value,
            perPage: perPage.value,
            sortKey: sortKey.value || null,
            sortDir: sortDir.value || null,
            search: search.value || null,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    )
}
const sendDebounced = debounced(send, 400)

const table = useVueTable({
    data: rows.value,
    columns,
    state: {
        get sorting() {
            return sorting.value
        },
        set sorting(updater) {
            sorting.value = typeof updater === "function"
                ? updater(sorting.value)
                : updater
        },
    },
    onSortingChange: updater => {
        // update sorting state for the frontend table
        sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
        page.value = 1 // reset to first page when sorting changes
        const sort = sorting.value[0]
        // send request with new sorting
        // update sorting state for the server request
        sortKey.value = sort?.id ?? null
        sortDir.value = sort ? (sort.desc ? "desc" : "asc") : null

        console.log("Sorting changed:", {
            sorting: sorting.value,
            sortKey: sortKey.value,
            sortDir: sortDir.value
        })

        send()
    },
    //manualSorting: true,
    //getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
    pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
})

// react to Inertia updates (when server responds)
watch(
    () => data,
    (props: PaginationObject) => {
        rows.value = props.data
        lastPage.value = props.last_page ?? 1
        prevPageUrl.value = props.prev_page_url ?? null
        nextPageUrl.value = props.next_page_url ?? null
        table.setOptions(prev => ({ ...prev, data: rows.value }))
    }
)

watch([page, perPage], send)
watch(search, () => {
    page.value = 1
    sendDebounced()
})
</script>

<template>
    <div class="p-4 space-y-3">
        <div class="flex gap-2 items-center">
            <input
                v-model="search"
                type="search"
                placeholder="Search…"
                class="border rounded px-2 py-1 w-64"
            />
            <select v-model.number="perPage" class="border rounded px-2 py-1">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="rows.length">
                        <template v-for="row in table.getRowModel().rows" :key="row.id">
                            <TableRow>
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                    {{ cell.getValue() }}
                                </TableCell>
                            </TableRow>
                        </template>
                    </template>
                    <TableRow v-else>
                        <TableCell :colspan="columns.length" class="h-24 text-center">
                            No results.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-between mt-4">
            <div>Page {{ page }} of {{ lastPage }} ({{ total }} rows)</div>
            <div class="space-x-2">
                <Button
                    :disabled="!prevPageUrl"
                    @click="() => { page--; }"
                >
                    Previous
                </Button>
                <Button
                    :disabled="!nextPageUrl"
                    @click="() => { page++; }"
                >
                    Next
                </Button>
            </div>
        </div>

    </div>
</template>
