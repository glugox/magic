<script setup lang="ts" generic="T">
import {defineProps, onMounted} from "vue"
import {useResourceTable} from "@/composables/useResourceTable"
import {ResourceTableProps} from "@/types/support"
import Toolbar from "@/components/resource-table/toolbar/Toolbar.vue";
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {FlexRender} from "@tanstack/vue-table";
import Pagination from "@/components/resource-table/Pagination.vue";
import {usePage} from '@inertiajs/vue3';
import {toast} from "vue-sonner"
import {useEntityContext} from "@/composables/useEntityContext";
import {Toaster} from '@/components/ui/sonner'
import TableFiltersBox from "@/components/filters/TableFiltersBox.vue";

const props = defineProps<ResourceTableProps<T>>()

// Entity context for Laravel logic mapping
const {createUrl} = useEntityContext(props.entity, props.parentEntity, props.parentId);

// generate random id if not set in props.state.settings.tableId
const tableId = props.state?.settings?.tableId || `table-${Math.random().toString(36).substring(2, 9)}`

// Composable for handling tanstack table state and data
const {table, rows, page, perPage, total, performBulkAction, bulkActionProcessing} = useResourceTable(props, tableId)

// Inertia page for flash messages
const inertiaPage = usePage()


onMounted(() => {
    if ((inertiaPage.props.flash as Record<string, any>)?.success) {
        toast((inertiaPage.props.flash as Record<string, any>)?.success, {
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
    <TableFiltersBox
        :table-id="tableId"
        :entity="entity"
        :initial-filters="props.state?.filters"
    />
    <Toolbar
        class="mb-4"
        :table-id="tableId"
        :parent-id="props.parentId"
        :initial-state="props.state"
        :columns="props.state?.settings?.allColumns"
        :bulk-action-processing="bulkActionProcessing"
        :entity="props.entity"
        :createUrl="createUrl"
        @bulk-action="performBulkAction"
        @update:visible-columns="setColumnsVisibility"
    />
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
                    <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()"/>
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
    <Pagination :disabled="bulkActionProcessing" :total="total" :per-page="perPage" :page="page"
                @update:page="p => (page = p)"/>
    <Toaster position="top-right"/>
</template>
