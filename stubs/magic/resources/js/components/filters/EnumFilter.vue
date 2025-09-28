<template>
    <BaseFilter
        :label="label"
        @change="onChange"
        @reset="onReset"
    >
        <template #default="{ values }">
            <Select v-model="values.selected">
                <SelectTrigger class="w-full">
                    <SelectValue>
                        {{ options?.find(opt => opt.name === values.selected)?.label ?? 'Select ' + label }}
                    </SelectValue>
                </SelectTrigger>

                <SelectContent>
                    <SelectItem
                        v-for="opt in options"
                        :key="opt.name"
                        :value="opt.name"
                        class="cursor-pointer rounded px-2 py-1.5 hover:bg-muted/50"
                    >
                        {{ opt.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select"
import BaseFilter from "@/components/filters/BaseFilter.vue";
import type { FilterConfig } from "@/types/support"

const props = defineProps<FilterConfig>()

const emit = defineEmits<{
    (e: "change", values: { selected: string | null }): void
    (e: "reset"): void
}>()

const onChange = (values: { selected: string | null }) => emit("change", values)
const onReset = () => emit("reset")

const { label, options } = props
</script>
