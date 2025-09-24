<script setup lang="ts" generic="T">
import {defineProps, onMounted, toRef} from "vue"
import { useResourceTable } from "@/composables/useResourceTable"
import {Entity, DbId, TableFilters, PaginatedResponse, ResourceData, Controller} from "@/types/support"
import Toolbar from "@/components/resource-table/toolbar/Toolbar.vue";
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {ColumnDef, FlexRender} from "@tanstack/vue-table";
import Pagination from "@/components/resource-table/Pagination.vue";
import { usePage } from '@inertiajs/vue3';
import { toast } from "vue-sonner"
import DebugBox from "@/components/debug/DebugBox.vue";
import {useEntityContext} from "@/composables/useEntityContext";
import { Toaster } from '@/components/ui/sonner'


export interface ResourceTableProps<T> {
    entity: Entity
    parentEntity?: Entity
    columns: ColumnDef<ResourceData>[]
    data: PaginatedResponse<T>
    parentId?: DbId
    filters?: TableFilters
}

const props = defineProps<ResourceTableProps<T>>()




const {createUrl} = useEntityContext(props.entity, props.parentEntity, props.parentId);

const {
    table, rows, page, perPage, total, search, selectedIds,
    performBulkAction, bulkActionProcessing
} = useResourceTable(props)

const inertiaPage = usePage()

onMounted(() => {
    if(inertiaPage.props.flash.success) {
        toast(inertiaPage.props.flash.success, {
            description: '',
            action: {
                label: 'Undo',
                onClick: () => console.log('Undo'),
            },
        })
    }
})

const setColumnsVisibility = (visibleColumns: string[]) => {
    props.columns.forEach(column => {
        table.getColumn(<string>column.id)?.toggleVisibility(visibleColumns.includes(<string>column.id))
    })
}

</script>

<template>
    <DebugBox v-bind="props" />
    <Toolbar
        class="mb-2"
        @update:search="value => search = value"
        :parent-id="props.parentId"
        :initial-filters="props.filters"
        :columns="props.filters?.allColumns"
        @bulk-action="performBulkAction"
        @update:visible-columns="setColumnsVisibility"
        :bulk-action-processing="bulkActionProcessing"
        :entity="props.entity"
        :createUrl="createUrl"
    />
    <div class="rounded-md border">
        <Table>
            <!-- headers -->
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
            <!-- rows -->
            <TableBody>
                <template v-if="rows.length">
                    <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                    </TableRow>
                </template>
                <TableRow v-else>
                    <TableCell :colspan="props.columns.length" class="h-24 text-center">
                        No results.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
    <Pagination :disabled="bulkActionProcessing" :total="total" :per-page="perPage" :page="page" @update:page="p => (page = p)" />
    <Toaster position="top-right" />
</template>
