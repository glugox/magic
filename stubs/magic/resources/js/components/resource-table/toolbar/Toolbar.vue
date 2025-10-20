<script setup lang="ts">
import {Filter, Loader} from "lucide-vue-next"
import ToolBarActions from "@/components/resource-table/toolbar/ToolBarActions.vue"
import {Entity, DbId, WayfinderRoute, DataTableState, EntityAction} from "@/types/support"
import {useTableProps} from "@/composables/useTableProps"
import ColumnVisibilityMenu from "@/components/resource-table/toolbar/ColumnVisibilityMenu.vue";
import NewEntityButton from "@/components/resource-table/toolbar/NewEntityButton.vue";
import TableSearch from "@/components/resource-table/toolbar/TableSearch.vue";
import {computed} from "vue";

// props
const {tableId, entity, createUrl, bulkActionProcessing, initialState} =
    defineProps<{
        tableId: string
        entity: Entity
        createUrl?: WayfinderRoute
        addNewUrl?: string
        parentId?: DbId
        bulkActionProcessing?: boolean
        initialState?: DataTableState
        selectedCount?: number
    }>()

// emits
const emit = defineEmits<{
    (e: "update:visibleColumns", value: string[]): void
    (e: "toolbar-action", action: EntityAction): void
    (e: "action", value: string): void
}>()

// use composable
const {visibleColumns, toggleColumnVisibility} = useTableProps(emit, initialState)

const canAddNew = computed(() => {
    return createUrl && createUrl?.url?.length > 0
})

const hasFilters = computed(() => {
    return entity.filters && entity.filters.length > 0
})

const hasActions = computed(() => {
    return Array.isArray(entity.actions) && entity.actions.length > 0
})

</script>

<template>
    <div v-bind="$attrs" class="flex gap-2 items-center justify-between">
        <TableSearch :table-id="tableId" placeholder="Search all columns…"/>
        <div class="flex items-center gap-2">
            <Loader v-if="bulkActionProcessing" class="w-4 h-4 mr-2 animate-spin"/>
            <!-- Filters Toggle -->
            <div v-if="hasFilters" >
                <div class="flex items-center cursor-pointer select-none"
                     @click="emit('action', 'toggle-filters')"
                >
                    <Filter class="w-5 h-5 text-muted-foreground" />
                </div>
            </div>
            <ColumnVisibilityMenu
                :columns="initialState?.settings?.allColumns"
                :visible-columns="visibleColumns "
                @toggle-column="toggleColumnVisibility"/>
            <NewEntityButton
                v-if="canAddNew"
                :label="`New ${entity.singularName}`"
                :createUrl="createUrl?.url"
            />
            <ToolBarActions
                v-if="hasActions"
                :actions="entity.actions ?? []"
                :disabled="bulkActionProcessing"
                :selected-count="selectedCount ?? 0"
                @action="(action) => emit('toolbar-action', action)"
            />
        </div>
    </div>
</template>
