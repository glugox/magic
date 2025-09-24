<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type { FormFieldProps } from '@/types/support'

const props = defineProps<FormFieldProps & { modelValue?: string }>()
</script>

<template>
    <BaseField v-bind="props">
        <InputField
            type="password"
            v-model="props.modelValue"
            v-bind="props.extraInputProps"
        />
    </BaseField>
</template>
