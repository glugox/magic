<template>
    <div class="mb-2">
        <div class="flex justify-end">
            <!-- Filter Header (click to toggle) -->
            <div></div>
            <div
                class="flex align-middle text-center gap-2 cursor-pointer select-none mb-2"
                @click="showFilters = !showFilters"
            >
                <Filter class="w-5 h-5 text-muted-foreground" />
                <span class="ml-1 text-xs text-muted-foreground">
      </span>
            </div>
        </div>

        <!-- Filters Content with sidebar-like collapse transition -->
        <div
            class="overflow-hidden transition-[max-height] duration-300 ease-in-out"
            :style="{ maxHeight: showFilters ? maxHeight + 'px' : '0' }"
            ref="container"
        >
            <!-- Quick Pills -->
            <div class="grid grid-cols-12 gap-4 w-full mb-6">
                <QuickPills class="col-span-6" />
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-12 gap-4">
                <component
                    v-for="(filter, index) in entity.filters"
                    :is="filterComponents[filter.type]"
                    :key="index"
                    :field="filter.field"
                    :type="filter.type"
                    :initial-values="filter.initialValues"
                    :label="filter.field"
                    class="col-span-12 sm:col-span-6 md:col-span-2"
                />
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from "vue";
import { Filter } from "lucide-vue-next";

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
</script>
