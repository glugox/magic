<template>
    <BaseField v-bind="props">
        <Input
            type="number"
            step="0.01"
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
import {FormFieldProps } from '@/types/support'
import { ref, watch } from 'vue'

const props = defineProps<FormFieldProps>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue)
watch(model, (val) => emit('update:modelValue', val))
</script>
