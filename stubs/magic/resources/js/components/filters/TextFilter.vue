<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" class="absolute -top-1 right-0" />
        <Label >{{ label }}</Label>

        <div class="flex gap-2 mt-2">
            <InputField
                v-model="localValue"
                type="text"
                :placeholder="`Search by ${label.toLowerCase()}...`"
                class="flex-1 w-48 bg-background border border-input text-foreground placeholder:text-muted-foreground"
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
    val => emit("change", val)
);

const { label } = props;
</script>
