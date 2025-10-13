<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" class="absolute -top-1 right-0" />
        <Label class="mb-2">{{ label }}</Label>
        <Select v-model="localValue">
            <SelectTrigger class="w-full">
                <SelectValue>{{ selectedLabel }}</SelectValue>
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="opt in filter.options" :key="opt.name" :value="opt.name">
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

    </div>
</template>

<script setup lang="ts">
import { computed, toRef } from "vue";
import { useFilter } from "@/composables/useFilter";
import { Label, Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui";
import ResetButton from "@/components/ResetButton.vue";
import {FilterProps, TableFilterEmits} from "@/types/support";

const props = defineProps<FilterProps>();
const emit = defineEmits<TableFilterEmits>();
const { label } = props.filter;

// UseFilter composable to manage local value, dirty state, and reset
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"), // pass reactive ref
    (val) => emit("change", val)
);

// Compute the label of the selected option, e.g. "Active" instead of "active"
const selectedLabel = computed(() => {
    const selectedOption = filter.options?.find(opt => opt.name === localValue.value);
    return selectedOption ? selectedOption.label : "Select...";
});

const { filter } = props;
</script>
