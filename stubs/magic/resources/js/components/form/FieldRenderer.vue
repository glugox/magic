<template>
    <component
        :is="fieldComponent"
        v-bind="props"
        :model-value="modelValue"
        :crud-action-type="crudActionType"
        @update:modelValue="updateModelValue"
    />
</template>

<script setup lang="ts">
import StringField from './StringField.vue'
import NumberField from './NumberField.vue'
import DateField from './DateField.vue'
import { Field, CrudActionType } from '@/types/support'

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: any
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const componentsMap: Record<string, any> = {
    string: StringField,
    number: NumberField,
    date: DateField,
}

const fieldComponent = componentsMap[props.field.type] ?? StringField

function updateModelValue(value: any) {
    emit('update:modelValue', value)
}
</script>
