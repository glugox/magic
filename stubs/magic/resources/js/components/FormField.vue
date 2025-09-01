<template>
    <div class="grid gap-2">
        <Label for="email">{{ field.label }}</Label>
        <span v-if="field.required || field.rules?.includes('required')" class="text-red-500">*</span>
        <Input
            :type="field.type"
            class="mt-1 block w-full"
            :name="field.name"
            :default-value="modelValue"
            :required="field.required"
            :placeholder="field.label"
        />
        <InputError class="mt-2" :message="error" />
    </div>
</template>
<script setup lang="ts">
import { Input } from '@/components/ui/input/index.js';
import InputError from '@/components/InputError.vue';
import { Field } from '@/types/support.js';
import {Label} from '@/components/ui/label';

interface Props {
    error?: string;
    field: Field;
    item?: Record<string, any>;
    modelValue?: any;
}

const { error, field }: Props = defineProps<Props>();

</script>
