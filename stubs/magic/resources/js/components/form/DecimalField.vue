<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type {FormFieldEmits, FormFieldProps} from '@/types/support'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()
</script>

<template>
    <BaseField v-bind="props" v-slot="{ validate }">
        <InputField
            :name="props.field.name"
            :placeholder="`Enter ${props.field.label}...`"
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
