<template>
    <BaseField v-bind="props">
        <template #default="{ validate }">
            <div class="space-y-2">
                <Select :name="field.name" v-model="model" @update:modelValue="validate">
                    <SelectTrigger class="w-full">
                        <SelectValue :placeholder="`Select ${field.label ?? field.name}`" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>{{ field.label ?? field.name }}</SelectLabel>
                            <SelectItem
                                v-for="value in field.values"
                                :key="value"
                                :value="value"
                            >
                                {{ value }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseField from './BaseField.vue'
import { FormFieldProps } from '@/types/support'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

const props = defineProps<FormFieldProps>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue ?? null)

watch(model, (val) => emit('update:modelValue', val))
</script>
