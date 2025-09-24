<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { ref, watch } from 'vue'

const props = defineProps<{
    modelValue?: string | number
    id?: string
    name?: string
    type?: string
    hidden?: boolean
    placeholder?: string
    class?: HTMLAttributes['class']
    disabled?: boolean
    readonly?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()

// Reactive internal model
const modelValue = ref(props.modelValue ?? '')

// Sync props -> internal model
watch(() => props.modelValue, (val: any) => (modelValue.value = val))

// Emit updates on change
watch(modelValue, (val) => emit('update:modelValue', val))
</script>

<template>
    <input
        v-model="modelValue"
        :id="id"
        :name="name"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :hidden="hidden"
        :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      props.class
    )"
    />
</template>
