<script setup lang="ts">
import BaseField from './BaseField.vue';
import { Switch } from '@/components/ui/switch';
import {FormFieldEmits, FormFieldProps} from '@/types/support';
import { ref, watch } from 'vue';

const props = defineProps<FormFieldProps>();
const emit = defineEmits<FormFieldEmits>();

// Local reactive value
const localValue = ref(!!props.modelValue);

// Sync localValue -> parent
watch(localValue, val => emit('update:modelValue', val));

// Sync parent -> localValue
watch(() => props.modelValue, val => localValue.value = !!val);
</script>

<template>
    <BaseField v-bind="props">
        <Switch v-model="localValue" :name="field.name" />
        <!-- Hidden input for form submission -->
        <input type="checkbox" class="sr-only" :name="field.name" :value="localValue ? 1 : 0" />
    </BaseField>
</template>
