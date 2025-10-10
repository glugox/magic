<template>
    <div class="filter relative pr-4">
        <ResetButton v-if="isDirty" @click="reset" class="absolute top-0 right-0 -mt-1 -mr-1" />
        <Label>{{ label }}</Label>
        <Switch v-model="localValue" class="mt-3 mx-auto" />
    </div>
</template>

<script setup lang="ts">
import { toRef } from "vue";
import { useFilter } from "@/composables/useFilter";
import { Label, Switch } from "@/components/ui";
import ResetButton from "@/components/ResetButton.vue";
import type { FilterBaseProps, TableFilterEmits } from "@/types/support";

const props = defineProps<FilterBaseProps>();
const emit = defineEmits<TableFilterEmits>();

// localValue, isDirty, and reset come from the composable
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"),
    (val) => emit("change", val)
);

const { label } = props;
</script>
