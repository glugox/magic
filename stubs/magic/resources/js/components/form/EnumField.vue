<script setup lang="ts">
import { ref, watch } from "vue"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import BaseField from "./BaseField.vue"
import type { FormFieldProps } from "@/types/support"

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{ (e: "update:modelValue", value: any): void }>()

// Local reactive value
const selected = ref(props.modelValue ?? null)

// Watch local value and emit changes
watch(selected, (val) => emit("update:modelValue", val))

// Get enum options from field metadata
const options = props.field?.values ?? []
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <Select v-model="selected">
                <SelectTrigger class="w-[280px]">
                    <SelectValue placeholder="Select {{ props.field.label }}" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem v-for="opt in options" :key="opt" :value="opt">
                            {{ opt }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <select :name="field.name" class="hidden">
                <option v-for="value in field.values" :key="value" :value="value">
                    {{ value }}
                </option>
            </select>
        </template>
    </BaseField>
</template>
