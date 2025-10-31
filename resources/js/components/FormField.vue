<template>
    <div class="grid gap-2">
        <Label for="email">{{ field.label }}</Label>
        <span v-if="rules?.includes('required')" class="text-red-500">*</span>
        <Input
            :type="field.type"
            class="mt-1 block w-full"
            :name="field.name"
            :default-value="modelValue"
            :required="field.required"
            :placeholder="field.label"
        />
        <InputError class-name="mt-2" :message="error" />
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input/index.js';
import InputError from '@glugox/module/components/InputError.vue';
import { Field, CrudActionType, Entity} from '@glugox/module/types/support';
import {Label} from '@/components/ui/label';

interface Props {
    error?: string;
    field: Field;
    entity?: Entity;
    crudActionType: CrudActionType;
    item?: Record<string, any>;
    modelValue?: any;
}

const { error, field, crudActionType }: Props = defineProps<Props>();

// Computed
const rules = computed(() => field.rules[crudActionType])


</script>
