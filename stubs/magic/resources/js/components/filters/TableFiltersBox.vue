<template>
    <div class="mb-2 items-center">

        <div v-if="Object.keys(currentFilters).length" class="mb-2">
            <Label class="block mb-2 text-sm text-muted-foreground">Active Filters:</Label>

            <div class="flex flex-wrap gap-2">
                <template v-for="(filterValue, field) in currentFilters" :key="field">
                    <!-- Render a pill for each filter -->
                    <div
                        class="flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-800 px-3 py-1 text-xs font-medium border border-emerald-200"
                    >
                        <span class="font-semibold">{{ formatFilterLabel(field as string) }}:</span>
                        <span>{{ formatFilterValue(field as string, filterValue) }}</span>
                        <button
                            class="ml-1 hover:text-emerald-900"
                            @click="removeFilter(field as string)"
                        >
                            ✕
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Filters Content -->
        <div
            class="overflow-clip transition-[max-height] duration-300 ease-in-out"
            :style="{ maxHeight: filtersVisible ? maxHeight + 'px' : '0' }"
            ref="container"
        >

            <Label class="block mb-2 text-sm text-muted-foreground">Filters:</Label>

            <!-- Filters Grid -->
            <div class="flex flex-wrap gap-4 pb-3">
                <component
                    v-for="(filter, index) in filtersMetaFull"
                    :is="filterComponents[filter.type]"
                    :key="filter.field + index"
                    :field="filter.field"
                    :type="filter.type"
                    :filter-value="currentFilters[filter.field]"
                    :options="filter.options"
                    :label="filter.label"
                    @change="setFilterValue(tableId, filter.field, $event)"
                    @reset="removeFilter(filter.field)"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {ref, onMounted, nextTick, computed, watch} from "vue"
import { Filter } from "lucide-vue-next"
import { Label } from "@/components/ui/label"

import EnumFilter from "./EnumFilter.vue"
import BelongsToManyFilter from "@/components/filters/BelongsToManyFilter.vue"
import RangeFilter from "@/components/filters/RangeFilter.vue"
import DateFilter from "@/components/filters/DateFilter.vue"
import BooleanFilter from "@/components/filters/BooleanFilter.vue"

import {
    DataTableFilters,
    Entity, type FilterValue,
    TableFiltersEmits, TableId,
} from "@/types/support"

import {setFilterValue, useFilters} from "@/store/tableFiltersStore";
import {formatDate} from "@/lib/app";
import TextFilter from "@/components/filters/TextFilter.vue";
import HasManyFilter from "@/components/filters/HasManyFilter.vue";
import HasOneFilter from "@/components/filters/HasOneFilter.vue";

const props = defineProps<{
    tableId: TableId
    entity: Entity
    initialFilters?: DataTableFilters,
    filtersVisible?: boolean
}>()

const emit = defineEmits<TableFiltersEmits>()

onMounted(() => {
    nextTick(() => {
        if (container.value) maxHeight.value = container.value.scrollHeight
    })
})

//const showFilters = ref(props.filtersVisible ?? false)
const container = ref<HTMLElement | null>(null)
const maxHeight = ref(0)

// Raw filters defined in entityMeta
const filtersMeta = ref(props.entity.filters || [])
const currentFilters = useFilters(props.tableId)

// Filters with extra data got from entity fields
const filtersMetaFull = ref(
    filtersMeta.value.map((filter) => {
        const fieldMeta = props.entity.fields.find((f) => f.name === filter.field)
        return {
            ...filter,
            label: filter.label || fieldMeta?.label || filter.field,
            type: filter.type,
            options: fieldMeta?.options || [],
        }
    })
)

// Watch props.filtersVisible to show/hide filters
/*watch(() => props.filtersVisible, (newVal) => {
    showFilters.value = newVal ?? false
    if (newVal && container.value) {
        nextTick(() => {
            if (container.value) maxHeight.value = container.value.scrollHeight
        })
    }
})*/

// Check if entity has any filters. This is not same as has active filters.
// Active filters are in currentFilters , when user has applied some
// filters. Here we check if entity has any filters defined at all.
const hasFilters = computed(() => {
    return (props.entity.filters ?? []).length > 0
})

// Map filter types to components
const filterComponents: Record<string, any> = {
    enum: EnumFilter,
    range: RangeFilter,
    date_range: DateFilter,
    boolean: BooleanFilter,
    text: TextFilter,
    belongs_to_many: BelongsToManyFilter,
    has_many: HasManyFilter,
    has_one: HasOneFilter,
}

function removeFilter(field: string) {
    console.log("Removing filter", field)
    setFilterValue(props.tableId, field, null)
}

function formatFilterValue(field: string, filter: FilterValue): string {
    if (filter == null) return "–";

    // Simple scalars
    if (typeof filter === "string" || typeof filter === "number") {
        return String(filter);
    }

    if (typeof filter === "boolean") {
        return filter ? "Yes" : "No";
    }

    // Get filter type from metadata
    const filterMeta = filtersMetaFull.value.find(f => f.field === field);
    if (!filterMeta) return "–";

    // Array values
    if (Array.isArray(filter)) {
        // Range: [min, max]
        if (filter.length === 2) {
            const [a, b] = filter;
            // If both undefined/null → empty
            if (a == null && b == null) return "–";
            return `${field} : ${a ?? "–"} → ${b ?? "–"}`;
        }

        return (filter as unknown[]).length ? (filter as unknown[]).join(", ") : "–";
    }

    if (typeof filter === "object") {
        const entries = Object.entries(filter as Record<string, any>);
        if (!entries.length) return "–";

        //For date range we need to format the min max dates
        if (filterMeta.type === "date_range" && entries.length === 2) {
            const [a, b] = entries.map(([, val]) => val ? formatDate(val) : null);
            return `${a ?? "–"} → ${b ?? "–"}`;
        }

        return entries
            .map(([, val]) => `${val ?? "–"}`)
            .join(" → ");
    }

    // Fallback for unexpected objects
    return JSON.stringify(filter);
}

function formatFilterLabel(field: string): string {
    const filterMeta = filtersMetaFull.value.find(f => f.field === field);
    return filterMeta ? filterMeta.label : field;
}

</script>
