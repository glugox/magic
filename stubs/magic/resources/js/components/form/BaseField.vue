<template>
    <div class="space-y-1">
        <label :for="field.name" class="block text-sm font-medium">
            {{ field.label }}
        </label>

        <!-- If consumer provides slot, render it -->
        <slot :validate="validate">
            <!-- Fallback if no slot provided -->
            <component
                :is="fieldComponent"
                :model-value="modelValue"
                @update:model-value="emit('update:modelValue', $event)"
                :id="field.name"
                :placeholder="field.placeholder"
                class="w-full"
            />
        </slot>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import {computed} from "vue";

const props = defineProps<{
    modelValue: any
    field: any
    error?: string
}>()

const emit = defineEmits(["update:modelValue"])

// dummy validate function for slot
function validate(value: any) {
    console.log("validating", value)
}

const fieldComponent = computed(() => {
    return props.field.component ?? "input"
})
</script>
