<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type { FormFieldProps } from '@/types/support'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>

<template>
    <BaseField v-bind="props" v-slot="{ validate }">
        <InputField
            type="number"
            step="0.01"
            inputmode="decimal"
            :model-value="props.modelValue"
            @update:model-value="(val: any) => {
                emit('update:modelValue', val)
                validate(val)
            }"
        />
    </BaseField>
</template>
