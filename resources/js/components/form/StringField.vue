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
            type="text"
            :name="props.field.name"
            :placeholder="`Enter ${props.field.label}...`"
            :model-value="props.modelValue"
            @update:model-value="(val) => {
                emit('update:modelValue', val)
                validate(val)
            }"
        />
    </BaseField>
</template>
