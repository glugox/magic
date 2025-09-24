<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type { FormFieldProps } from '@/types/support'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
}>()
</script>

<template>
    <BaseField v-bind="props" v-slot="{ validate }">
        <InputField
            type="text"
            :model-value="props.modelValue"
            @update:model-value="(val) => {
                emit('update:modelValue', val)
                validate(val)
            }"
        />
    </BaseField>
</template>
