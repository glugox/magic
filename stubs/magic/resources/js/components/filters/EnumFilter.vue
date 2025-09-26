<template>
    <BaseFilter :label="label">
        <Select v-model="model">
            <SelectTrigger class="w-full">
                <SelectValue :placeholder="'Select ' + label" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="(label, value) in options"
                    :key="value"
                    :value="value"
                    class="cursor-pointer rounded px-2 py-1.5 hover:bg-muted/50"
                >
                    {{ label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </BaseFilter>
</template>

<script setup lang="ts">
import BaseFilter from "./BaseFilter.vue"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import {computed} from "vue";
import {FilterConfig} from "@/types/support";

const props = defineProps<FilterConfig>()

const emit = defineEmits(["update:modelValue"])

const model = computed({
    get: () => props.initialValues,
    set: (value: string | null) => emit("update:modelValue", value),
})

</script>
