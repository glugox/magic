<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" />
        <Label>{{ label }}</Label>

        <div class="flex gap-2 mt-2">
            <InputField
                v-model="localValue.min"
                type="number"
                class="flex-1 w-22 bg-background border border-input text-foreground placeholder:text-muted-foreground"
            />
            <InputField
                v-model="localValue.max"
                type="number"
                class="flex-1 w-22 bg-background border border-input text-foreground placeholder:text-muted-foreground"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { toRef } from "vue";
import { useFilter } from "@/composables/useFilter";
import { Label } from "@/components/ui";
import ResetButton from "@/components/ResetButton.vue";
import InputField from "@/components/form/InputField.vue";
import type { FilterBaseProps, TableFilterEmits } from "@/types/support";

const props = defineProps<FilterBaseProps>();
const emit = defineEmits<TableFilterEmits>();

const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"), // pass reactive ref
    val => emit("change", val),
    {
        defaultValue: { min: null, max: null } // or nulls if you prefer
    }
);

const { label } = props;
</script>
