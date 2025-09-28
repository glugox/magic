<template>
    <div class="py-0 relative">
        <Label class="mb-3">{{ label }}</Label>

        <!-- Default slot passes reactive values -->
        <slot :values="values"></slot>

        <!-- Reset button only if dirty -->
        <button
            v-if="isDirty"
            class="absolute -top-1 right-0 text-muted-foreground hover:text-foreground"
            @click="reset"
        >
            ✕
        </button>
    </div>
</template>

<script setup lang="ts">
import { reactive, watch, computed } from "vue"
import { Label } from "@/components/ui/label"

interface BaseFilterProps<T extends Record<string, any>> {
    label: string
    initialValues?: T
}

// make the emit generic over T
const props = defineProps<BaseFilterProps<any>>()
const emit = defineEmits<{
    <T extends Record<string, any>>(e: "change", payload: T): void
    (e: "reset"): void
}>()

const values = reactive({ ...(props.initialValues || null) })

const isDirty = computed(() => {
    if (!props.initialValues) return Object.keys(values).length > 0
    return Object.keys(props.initialValues).some(key => values[key] !== props.initialValues?.[key])
})

watch(values, (newVal) => emit("change", newVal as Record<string, any>), { deep: true })

const reset = () => {
    console.log("Resetting filter", props.label)
    // Reset values
    Object.keys(values).forEach(key => {
        if (props.initialValues && key in props.initialValues) {
            values[key] = props.initialValues[key]
        } else {
            delete values[key]
        }
    })
    emit("reset")
}
</script>
