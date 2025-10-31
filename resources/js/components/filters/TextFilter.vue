<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" class="absolute -top-1 right-0" />
        <Label >{{ label }}</Label>

        <div class="flex gap-2 mt-2">
            <InputField
                v-model="localValue"
                type="text"
                :placeholder="`Search by ${label?.toLowerCase()}...`"
                class="flex-1 w-48 bg-background border border-input text-foreground placeholder:text-muted-foreground"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { toRef } from "vue";
import { useFilter } from "@glugox/module/composables/useFilter";
import { Label } from "@/components/ui";
import ResetButton from "@glugox/module/components/ResetButton.vue";
import InputField from "@glugox/module/components/form/InputField.vue";
import type {FilterProps, TableFilterEmits} from "@glugox/module/types/support";

const props = defineProps<FilterProps>();
const emit = defineEmits<TableFilterEmits>();

const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"), // pass reactive ref
    val => emit("change", val)
);

const { filter } = props;
const { label } = filter;
</script>
