<template>
    <BaseField v-bind="props" :error="error" v-model="model">
        <template #default="{ validate }">
            <div class="flex items-center gap-2">
                <Switch
                    :id="field.name"
                    :name="field.name"
                    v-model="model"
                    @blur="validate(model)"
                />
            </div>
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from '@/components/form/BaseField.vue'
import { Switch } from '@/components/ui/switch'
import { Field, CrudActionType } from '@/types/support'
import { ref, watch } from 'vue'

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: boolean
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const model = ref(props.modelValue ?? false)
watch(model, (val) => emit('update:modelValue', val))
</script>
