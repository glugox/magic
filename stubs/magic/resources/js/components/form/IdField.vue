<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type { FormFieldProps } from '@/types/support'

const props = defineProps<FormFieldProps>()
</script>

<template>
    <BaseField v-bind="props">
        <InputField
            :name="props.field.name"
            type="text"
            :model-value="props.modelValue"
            readonly
            hidden
            class="bg-muted cursor-not-allowed"
        />
    </BaseField>
</template>
