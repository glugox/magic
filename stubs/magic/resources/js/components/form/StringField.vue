<template>
    <BaseField v-bind="props">
        <template #default="{ validate }">
            <Input
                type="text"
                :id="field.name"
                :name="field.name"
                :placeholder="field.label"
                v-model="model"
                @blur="validate(model)"
            />
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from './BaseField.vue'
import { Input } from '@/components/ui/input'
import { FormFieldProps } from '@/types/support'
import { ref, watch } from 'vue'

const props = defineProps<FormFieldProps>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue)
watch(model, (val) => emit('update:modelValue', val))
</script>
