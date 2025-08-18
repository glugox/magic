<script setup lang="ts">
import { ref, watch, computed } from "vue"
import { router, usePage } from "@inertiajs/vue3"
import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
    SortingState,
    FlexRender
} from "@tanstack/vue-table"
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Entity } from '@/types/app'

type User = {
    id: number
    name: string
    email: string
    created_at: string
}

interface PaginationObject {
    data: any[]
    total: number
    current_page: number
    per_page: number
    search?: string
    sort_key?: string
    last_page?: number
    sort_dir?: "asc" | "desc"
    prev_page_url?: string | null
    next_page_url?: string | null
    [key: string]: any
}


const { data, columns, entityMeta } = defineProps<{
    data: PaginationObject,
    columns ?: any[],
    entityMeta?: Entity
}>()

console.log("Initial page:", data)

const rows = ref<User[]>(data.data)
const page = ref(data.current_page)
const perPage = ref(data.per_page)
const total = ref(data.total)
const search = ref(data.search ?? "")
const lastPage = ref(data.last_page ?? 1)
const prevPageUrl = ref(data.prev_page_url ?? null)
const nextPageUrl = ref(data.next_page_url ?? null)
const sorting = ref<SortingState>(
    data.sort_key && data.sort_dir
        ? [{ id: data.sort_key, desc: data.sort_dir === "desc" }]
        : []
)

// Only send the text; server decides which columns are searchable.
const debounced = (fn: Function, ms = 400) => {
    let t: number | undefined
    return (...args: any[]) => {
        clearTimeout(t)
        // @ts-ignore
        t = setTimeout(() => fn(...args), ms)
    }
}
const send = () => {
    const sort = sorting.value[0]
    router.get(
        route(entityMeta.resourcePath),
        {
            page: page.value,
            perPage: perPage.value,
            sortKey: sort?.id ?? null,
            sortDir: sort ? (sort.desc ? "desc" : "asc") : null,
            search: search.value || null,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    )
}
const sendDebounced = debounced(send, 400)

const table = useVueTable({
    data: rows.value,
    columns,
    state: { sorting: sorting.value },
    onSortingChange: updater => {
        sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
        page.value = 1 // reset to first page when sorting changes
        send()
    },
    manualSorting: true,
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
    pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
})

// react to Inertia updates (when server responds)
watch(
    () => data,
    (props: PaginationObject) => {
        rows.value = props.data
        total.value = props.total
        page.value = props.current_page
        perPage.value = props.per_page
        lastPage.value = props.last_page ?? 1
        prevPageUrl.value = props.prev_page_url ?? null
        nextPageUrl.value = props.next_page_url ?? null
        sorting.value =
            props.sort_key && props.sort_dir
                ? [{ id: props.sort_key, desc: props.sort_dir === "desc" }]
                : []
        search.value = props.search ?? ""
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
                    @click="prevPageUrl && router.get(prevPageUrl, {}, { preserveState: true })"
                >
                    Previous
                </Button>
                <Button
                    :disabled="!nextPageUrl"
                    @click="nextPageUrl && router.get(nextPageUrl, {}, { preserveState: true })"
                >
                    Next
                </Button>
            </div>
        </div>

    </div>
</template>
