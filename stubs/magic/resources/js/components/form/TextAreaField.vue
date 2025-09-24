<template>
    <BaseField v-bind="props">
        <template #default="{ validate }">
      <Textarea
          :id="field.name"
          :name="field.name"
          :placeholder="`Enter ${field.label}...`"
          :rows="4"
          v-model="model"
          @blur="validate(model)"
      />
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from './BaseField.vue'
import { Textarea } from '@/components/ui/textarea'
import { ref, watch } from 'vue'
import { FormFieldProps } from '@/types/support'

const props = defineProps<FormFieldProps>()
const emit = defineEmits(['update:modelValue'])

// Local model for v-model binding
const model = ref(props.modelValue ?? '')

// Emit changes up to parent
watch(model, (val) => emit('update:modelValue', val))

// Sync with external changes
watch(() => props.modelValue, (val) => {
    if (val !== model.value) {
        model.value = val ?? ''
    }
})
</script>
