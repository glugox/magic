<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type {FormFieldEmits, FormFieldProps} from '@glugox/module/types/support'

const props = defineProps<FormFieldProps>()
const emit = defineEmits<FormFieldEmits>()
</script>

<template>
    <BaseField v-bind="props" v-slot="{ validate }">
        <InputField
            type="number"
            :name="props.field.name"
            :placeholder="`Enter ${props.field.label}...`"
            inputmode="decimal"
            :model-value="props.modelValue"
            @update:model-value="(val: any) => {
                emit('update:modelValue', val)
                validate(val)
            }"
        />
    </BaseField>
</template>
