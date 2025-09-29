<script setup lang="ts">
import { ref, watch } from "vue"
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@/components/ui/select"
import BaseField from "./BaseField.vue"
import {FormFieldEmits, FormFieldProps} from "@/types/support"

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()

// Local reactive value
const selected = ref(props.modelValue ?? null)

// Local → parent
watch(selected, (val) => emit("update:modelValue", val))

// Parent → local (needed for reset and external changes)
watch(
    () => props.modelValue,
    (val) => {
        selected.value = val ?? null
    }
)

// Enum options
const options = props.field?.options ?? []
</script>

<template>
    <BaseField v-bind="props">
        <template #default>
            <Select v-model="selected">
                <SelectTrigger class="w-[280px]">
                    <SelectValue :placeholder="`Please select ${props.field.label}...`" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <!-- Null option -->
                        <SelectItem :value="null">
                            Please select {{ props.field.label }}...
                        </SelectItem>

                        <!-- Enum options -->
                        <SelectItem v-for="opt in options" :key="opt.name" :value="opt.name">
                            {{ opt.label }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>

            <!-- Hidden real select for form post -->
            <select  :name="field.name" class="sr-only" v-model="selected">
                <option :value="null">Please select {{ props.field.label }}...</option>
                <option v-for="value in field.options" :key="value.name" :value="value.name">
                    {{ value.label }}
                </option>
            </select>
        </template>
    </BaseField>
</template>
