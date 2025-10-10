<template>
    <div class="relative mb-4">
        <!-- Active Filters -->
        <div v-if="Object.keys(currentFilters).length" class="mb-4">
            <ResetButton class="absolute top-2 right-2" @click="reset" />
            <Label class="block mb-2 text-sm text-muted-foreground">Active Filters:</Label>
            <div
                class="flex flex-wrap gap-2 bg-muted/30 rounded-lg p-2"
            >
                <template v-for="(filterValue, field) in currentFilters" :key="field">
                    <div
                        class="flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-800 px-3 py-1 text-xs font-medium border border-emerald-200 hover:bg-emerald-100 transition"
                    >
                        <span class="font-semibold">{{ formatFilterLabel(field as string) }}:</span>
                        <span>{{ formatFilterValue(field as string, filterValue) }}</span>
                        <button
                            class="ml-1 text-emerald-700 hover:text-emerald-900 transition"
                            @click="removeFilter(field as string)"
                        >
                            ✕
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Filters Content -->
        <Transition name="expand">
            <div v-show="filtersVisible" class="overflow-hidden">
                <Label class="block mb-2 text-sm text-muted-foreground">Filters:</Label>

                <div
                    class="grid gap-4 pb-3 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]"
                >
                    <div
                        v-for="(filter, index) in filtersMetaFull"
                        :key="filter.field + index"
                        class="bg-card rounded-lg p-3 shadow-sm hover:shadow-md transition"
                    >
                        <component
                            :is="filterComponents[filter.type]"
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
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, computed } from "vue"
import { Label } from "@/components/ui/label"

import EnumFilter from "./EnumFilter.vue"
import BelongsToManyFilter from "@/components/filters/BelongsToManyFilter.vue"
import RangeFilter from "@/components/filters/RangeFilter.vue"
import DateFilter from "@/components/filters/DateFilter.vue"
import BooleanFilter from "@/components/filters/BooleanFilter.vue"
import TextFilter from "@/components/filters/TextFilter.vue"
import HasManyFilter from "@/components/filters/HasManyFilter.vue"
import HasOneFilter from "@/components/filters/HasOneFilter.vue"

import { setFilterValue, useFilters } from "@/store/tableFiltersStore"
import { formatDate } from "@/lib/app"
import type {
    DataTableFilters,
    Entity,
    FilterValue,
    TableFiltersEmits,
    TableId,
} from "@/types/support"
import ResetButton from "@/components/ResetButton.vue";

const props = defineProps<{
    tableId: TableId
    entity: Entity
    initialFilters?: DataTableFilters
    filtersVisible?: boolean
}>()

const emit = defineEmits<TableFiltersEmits>()

const container = ref<HTMLElement | null>(null)
const maxHeight = ref(0)

onMounted(() => {
    nextTick(() => {
        if (container.value) maxHeight.value = container.value.scrollHeight
    })
})

const filtersMeta = ref(props.entity.filters || [])
const currentFilters = useFilters(props.tableId)

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
    setFilterValue(props.tableId, field, null)
}

function reset() {
    Object.keys(currentFilters).forEach((field) => {
        setFilterValue(props.tableId, field, null)
    })
    emit("reset")
}

function formatFilterValue(field: string, filter: FilterValue): string {
    if (filter == null) return "–"
    if (typeof filter === "string" || typeof filter === "number") return String(filter)
    if (typeof filter === "boolean") return filter ? "Yes" : "No"

    const filterMeta = filtersMetaFull.value.find((f) => f.field === field)
    if (!filterMeta) return "–"

    if (Array.isArray(filter)) {
        if (filter.length === 2) {
            const [a, b] = filter
            if (a == null && b == null) return "–"
            return `${a ?? "–"} → ${b ?? "–"}`
        }
        return (filter as string[]).length ? (filter as string[]).join(", ") : "–"
    }

    if (typeof filter === "object") {
        const entries = Object.entries(filter)
        if (!entries.length) return "–"

        if (filterMeta.type === "date_range" && entries.length === 2) {
            const [a, b] = entries.map(([, val]) => (val ? formatDate(val) : null))
            return `${a ?? "–"} → ${b ?? "–"}`
        }

        return entries.map(([, val]) => `${val ?? "–"}`).join(" → ")
    }

    return JSON.stringify(filter)
}

function formatFilterLabel(field: string): string {
    const filterMeta = filtersMetaFull.value.find((f) => f.field === field)
    return filterMeta ? filterMeta.label : field
}
</script>
