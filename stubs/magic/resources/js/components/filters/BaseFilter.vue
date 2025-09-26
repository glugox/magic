<template>
    <div class="py-3 relative">
        <Label class="mb-3">{{ label }}</Label>

        <!-- Default slot passes values -->
        <slot :values="values"></slot>

        <!-- Reset button only if dirty -->
        <button
            v-if="isDirty"
            class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
            @click="reset"
        >
            ✕
        </button>
    </div>
</template>

<script setup lang="ts">
import { reactive, watch, computed } from "vue"
import { Label } from "@/components/ui/label"
import type { FilterBaseProps } from "@/types/support"

const props = defineProps<FilterBaseProps>()

// Copy initial values into reactive state
const values = reactive({ ...(props.initialValues || {}) })

// Track if values differ from initialValues
const isDirty = computed(() => {
    return JSON.stringify(values) !== JSON.stringify(props.initialValues || {})
})

// Reset function
const reset = () => {
    Object.assign(values, props.initialValues || {})
}
</script>
