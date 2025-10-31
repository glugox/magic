<script setup lang="ts">
import BaseField from './BaseField.vue'
import InputField from './InputField.vue'
import type {FormFieldEmits, FormFieldProps} from '@glugox/module/types/support'
import { ExternalLink } from 'lucide-vue-next';

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
        <!-- Browse button only if URL is valid -->
        <a
            :href="props.modelValue"
            target="_blank"
            class="inline-flex items-center gap-1 text-xs transition text-muted-foreground"
        >
            <ExternalLink class="w-3 h-3" />
            Browse
        </a>
    </BaseField>
</template>
