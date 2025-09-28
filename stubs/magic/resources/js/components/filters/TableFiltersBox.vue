<template>


    <div class="mb-4">
        <!-- Active Filters Section -->
        <div v-if="Object.keys(activeFilters).length" class="mb-3">
            <Label class="block mb-2 text-sm text-muted-foreground">Active Filters:</Label>

            <div class="flex flex-wrap gap-2">
                <template v-for="(values, field) in activeFilters" :key="field">
                    <!-- Render a pill for each filter -->
                    <div
                        class="flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-800 px-3 py-1 text-xs font-medium border border-emerald-200"
                    >
                        <span class="font-semibold">{{ getFilterLabel(field) }}:</span>
                        <span>{{ formatFilterValue(field, values) }}</span>
                        <button
                            class="ml-1 hover:text-emerald-900"
                            @click="clearFilter(field)"
                        >
                            ✕
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Filters Toggle -->
        <div class="flex justify-end">
            <div
                class="flex items-center gap-2 cursor-pointer select-none mb-2"
                @click="showFilters = !showFilters"
            >
                <Filter class="w-5 h-5 text-muted-foreground" />
                <span class="ml-1 text-xs text-muted-foreground">
        </span>
            </div>
        </div>

        <!-- Filters Content -->
        <div
            class="overflow-clip transition-[max-height] duration-300 ease-in-out"
            :style="{ maxHeight: showFilters ? maxHeight + 'px' : '0' }"
            ref="container"
        >
            <!-- Filters Grid -->
            <div class="flex flex-wrap gap-4 pb-3">
                <component
                    v-for="(filter, index) in filtersFull"
                    :is="filterComponents[filter.type]"
                    :key="filter.field + '-' + filterKey[filter.field]"
                    :field="filter.field"
                    :type="filter.type"
                    :initial-values="filter.initialValues"
                    :options="filter.options"
                    :label="filter.label"
                    @change="updateFilter(filter.field, $event)"
                    @reset="clearFilter(filter.field)"
                />
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import {ref, onMounted, nextTick, reactive} from "vue";
import { Filter } from "lucide-vue-next";
import {Label} from "@/components/ui/label";

// Your filter components
import EnumFilter from "./EnumFilter.vue";
import ManyToManyFilter from "@/components/filters/ManyToManyFilter.vue";
import RangeFilter from "@/components/filters/RangeFilter.vue";
import DateFilter from "@/components/filters/DateFilter.vue";
import BooleanFilter from "@/components/filters/BooleanFilter.vue";
import QuickPills from "@/components/filters/QuickPills.vue";

import type { Entity } from "@/types/support";

const props = defineProps<{
    entity: Entity;
}>();

const showFilters = ref(false);
const container = ref<HTMLElement | null>(null);
const maxHeight = ref(0);

// Raw filters defined in entityMeta
const filters = ref(props.entity.filters || []);
const filterKey = reactive<Record<string, number>>({})

// Filters with extra data got from entity fields
const filtersFull = ref(
    filters.value.map((filter) => {
        const fieldMeta = props.entity.fields.find((f) => f.name === filter.field);
        return {
            ...filter,
            label: fieldMeta?.label || filter.field,
            type: filter.type || fieldMeta?.type || "enum",
            options: fieldMeta?.options || [],
            //initialValues: filter.initialValues || null,
        };
    })
);

onMounted(() => {
    nextTick(() => {
        if (container.value) maxHeight.value = container.value.scrollHeight;
    });
});

// Map filter types to components
const filterComponents: Record<string, any> = {
    enum: EnumFilter,
    many: ManyToManyFilter,
    range: RangeFilter,
    date_range: DateFilter,
    boolean: BooleanFilter,
};

const activeFilters = reactive<Record<string, any>>({})

const updateFilter = (field: string, values: any) => {
    console.log("Update filter:", field, values)

    if (
        values == null ||                         // null or undefined
        (typeof values === "object" && !Object.keys(values).length) || // {}
        (typeof values === "object" && Object.values(values).every(v => v == null)) // all nulls
    ) {
        delete activeFilters[field]
    } else {
        activeFilters[field] = values
    }

    console.log("Filters updated:", activeFilters)
}

const clearFilter = (field: string) => {
    console.log("Clear filter:", field)
    delete activeFilters[field]
    filterKey[field] = (filterKey[field] || 0) + 1 // force reset of component
    console.log("Filter cleared:", activeFilters)
}

const getFilterLabel = (field: string) => {
    const meta = props.entity.fields.find(f => f.name === field)
    return meta?.label || field
}

const formatFilterValue = (field: string, values: any) => {
    if (!values) return ""

    // Range filters (price, etc.)
    if ("min" in values || "max" in values) {
        return `${values.min ?? "–"} → ${values.max ?? "–"}`
    }

    // Date range
    if ("from" in values || "to" in values) {
        return `${values.from ?? "–"} → ${values.to ?? "–"}`
    }

    // Single selection
    if ("selected" in values) {
        return values.selected
    }

    // Boolean filter
    if (values && "active" in values) {
        return values.active ? "Yes" : "No"
    }

    // Fallback: JSON
    return JSON.stringify(values)
}

</script>
