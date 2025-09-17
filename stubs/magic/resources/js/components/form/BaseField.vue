<template>
    <div class="space-y-1">
        <label :for="field.name" v-if="!field.hidden" class="block text-sm font-medium">
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
import {FormFieldProps} from "@/types/support";

const props = defineProps<FormFieldProps>()

const emit = defineEmits(["update:modelValue"])

// dummy validate function for slot
function validate(value: any) {
    console.log("validating", value)
}

const fieldComponent = computed(() => {
    return props.field.component ?? "input"
})
</script>
