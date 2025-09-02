<template>
    <BaseField v-bind="props" :error="error">
        <Input
            type="number"
            :id="field.name"
            :name="field.name"
            :placeholder="field.label"
            v-model="model"
        />
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from './BaseField.vue'
import { Input } from '@/components/ui/input'
import { Field, CrudActionType } from '@/types/support'
import { ref, watch } from 'vue'

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: any
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue)
watch(model, (val) => emit('update:modelValue', val))
</script>
