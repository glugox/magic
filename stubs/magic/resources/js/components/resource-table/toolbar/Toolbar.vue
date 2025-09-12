<script setup lang="ts">
import { Loader } from "lucide-vue-next"
import ToolBarActions from "@/components/resource-table/toolbar/ToolBarActions.vue"
import {Entity, Controller, DbId, TableFilters, Column} from "@/types/support"
import { useTableFilters } from "@/composables/useTableFilters"
import ColumnVisibilityMenu from "@/components/resource-table/toolbar/ColumnVisibilityMenu.vue";
import NewEntityButton from "@/components/resource-table/toolbar/NewEntityButton.vue";
import TableSearch from "@/components/resource-table/toolbar/TableSearch.vue";

// props
const { entity, controller, parentId, bulkActionProcessing, initialFilters } =
    defineProps<{
        entity: Entity
        controller: Controller
        parentId?: DbId
        bulkActionProcessing?: boolean
        columns?: Column[]
        initialFilters?: TableFilters
    }>()

// emits
const emit = defineEmits<{
    (e: "update:search", value: string): void
    (e: "update:visibleColumns", value: string[]): void
    (e: "bulk-action", action: "edit" | "delete" | "archive"): void
}>()

// use composable
const { search, visibleColumns, toggleColumnVisibility } = useTableFilters(emit, initialFilters)

</script>

<template>
    <div class="flex gap-2 items-center justify-between">
        <div>
            <TableSearch @update:search="value => search = value" placeholder="Search…" />
        </div>
        <div class="flex items-center gap-2">
            <Loader v-if="bulkActionProcessing" class="w-4 h-4 mr-2 animate-spin" />
            <ColumnVisibilityMenu :columns="columns" :visible-columns="visibleColumns " @toggle-column="toggleColumnVisibility" />
            <NewEntityButton :entity="entity" :controller="controller" :parent-id="parentId" />
            <ToolBarActions @action="(action) => emit('bulk-action', action)" />
        </div>
    </div>
</template>
