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
            :name="props.field.name"
            :placeholder="`Enter ${props.field.label}...`"
            :autocomplete="'new-password'"
            :maxlength="props.field.max || 255"
            :minlength="props.field.min || 8"
            v-model="props.modelValue"
        />
    </BaseField>
</template>
