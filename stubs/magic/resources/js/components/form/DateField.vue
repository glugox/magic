<template>
    <BaseField v-bind="props" :error="error" v-model="model">
        <template #default="{ validate }">
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        class="w-full justify-start text-left font-normal"
                        @blur="validate(model)"
                    >
                        <span v-if="model">{{ formatDate(model) }}</span>
                        <span v-else class="text-muted-foreground">Pick a date</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-auto p-0">
                    <Calendar v-model="model" />
                </PopoverContent>
            </Popover>
        </template>
    </BaseField>
</template>

<script setup lang="ts">
import BaseField from './BaseField.vue'
import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Calendar } from '@/components/ui/calendar'
import { Field, CrudActionType } from '@/types/support'
import { ref, watch } from 'vue'

interface Props {
    error?: string
    field: Field
    crudActionType: CrudActionType
    modelValue?: Date | null
}
const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const model = ref<Date | null>(props.modelValue ?? null)

watch(model, (val) => emit('update:modelValue', val))

function formatDate(date: Date | null): string {
    if (!date) return ''
    const d = date.getDate().toString().padStart(2, '0')
    const m = (date.getMonth() + 1).toString().padStart(2, '0')
    const y = date.getFullYear()
    return `${d}.${m}.${y}`
}
</script>
