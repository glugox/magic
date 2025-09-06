<script setup lang="ts">
import { ref, watch, computed } from "vue"
import { router } from "@inertiajs/vue3"
import { getCoreRowModel, useVueTable, SortingState, FlexRender, ColumnDef } from "@tanstack/vue-table"
import Avatar from "@/components/Avatar.vue"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Entity, TableFilters, PaginatedResponse, Controller, DbId } from '@/types/support'
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationPrevious,
    PaginationNext,
    PaginationFirst,
    PaginationLast,
    PaginationItem
} from '@/components/ui/pagination';

// props
const { entity, data, parentId, columns, filters, controller } = defineProps<{
    entity?: Entity
    data: PaginatedResponse<any>
    parentId?: DbId
    columns: ColumnDef<any, any>[]
    filters?: TableFilters
    controller: any
}>()

// state
const rows = ref(data.data)
const page = ref(data.meta.current_page)
const perPage = ref(data.meta.per_page)
const total = ref(data.meta.total)
const lastPage = ref(data.meta.last_page)

const sorting = ref<SortingState>(
    filters?.sortKey ? [{ id: filters.sortKey, desc: filters.sortDir === "desc" }] : []
)
const sortKey = ref(filters?.sortKey ?? null)
const sortDir = ref(filters?.sortDir ?? null)
const search = ref(filters?.search ?? "")

// debounce helper
const debounced = (fn: Function, ms = 400) => {
    let t: number | undefined
    return (...args: any[]) => {
        clearTimeout(t)
        // @ts-ignore
        t = setTimeout(() => fn(...args), ms)
    }
}

// api call
const send = () => {
    const params: any = {
        page: page.value,
        perPage: perPage.value,
        search: search.value,
    }
    if (sortKey.value) params.sortKey = sortKey.value
    if (sortDir.value) params.sortDir = sortDir.value

    router.get(controller.index(parentId), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}
const sendDebounced = debounced(send, 400)

// table setup
const table = useVueTable({
    data: rows.value,
    columns,
    state: {
        get sorting() {
            return sorting.value
        },
        set sorting(updater) {
            sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
        },
    },
    onSortingChange: updater => {

        sorting.value = typeof updater === "function" ? updater(sorting.value) : updater
        page.value = 1
        const sort = sorting.value[0]
        sortKey.value = sort?.id ?? null
        sortDir.value = sort ? (sort.desc ? "desc" : "asc") : null
        send()
    },
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
    pageCount: computed(() => Math.ceil(total.value / perPage.value)).value,
})

// react to inertia updates
watch(
    () => data,
    (props: PaginatedResponse<any>) => {
        rows.value = props.data
        page.value = props.meta.current_page
        perPage.value = props.meta.per_page
        total.value = props.meta.total
        lastPage.value = props.meta.last_page
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

    {{ page }}
    <div class="space-y-3">
        <!-- Search + Create -->
        <div class="flex gap-2 items-center">
            <input
                v-model="search"
                type="search"
                placeholder="Search…"
                class="border rounded px-2 py-1 w-64"
            />
            <Button
                v-if="entity && controller && controller.create"
                :href="controller.create().url"
                as="a"
                class="ml-auto"
            >
                New {{ entity.singularName }}
            </Button>
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
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
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

        <!-- Updated Pagination Controls -->
        <!-- Pagination -->
        <div class="flex justify-between mt-4 items-center">
            <div>Page {{ page }} of {{ lastPage }} ({{ total }} rows)</div>

            <Pagination
                :total="total"
                :items-per-page="perPage"
                :default-page="page"
                :sibling-count="1"
                :show-edges="true"
                @update:page="(p) => { page = p }"
            >
                <PaginationContent v-slot="{ items }" class="flex space-x-1">
                    <PaginationFirst as-child>
                        <Button size="sm" :disabled="page === 1">« First</Button>
                    </PaginationFirst>

                    <PaginationPrevious as-child>
                        <Button size="sm" :disabled="page === 1">‹ Prev</Button>
                    </PaginationPrevious>

                    <template v-for="item in items" :key="item.type + '-' + item.value">
                        <PaginationItem v-if="item.type === 'page'" :value="item.value" as-child>
                            <Button
                                size="sm"
                                :variant="item.value === page ? 'default' : 'outline'"
                            >
                                {{ item.value }}
                            </Button>
                        </PaginationItem>
                        <PaginationEllipsis v-else :index="item.value" />
                    </template>

                    <PaginationNext as-child>
                        <Button size="sm" :disabled="page === lastPage">Next ›</Button>
                    </PaginationNext>

                    <PaginationLast as-child>
                        <Button size="sm" :disabled="page === lastPage">Last »</Button>
                    </PaginationLast>
                </PaginationContent>
            </Pagination>
        </div>

    </div>
</template>
