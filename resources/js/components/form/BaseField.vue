<script setup lang="ts">
import { FormFieldProps } from "@glugox/module/types/support";

const props = defineProps<FormFieldProps>();
// dummy validate function for slot
function validate(value: any) {
    console.log("validating::", value)
}
</script>

<template>
    <div class="mb-4">
        <label v-if="!field.hidden" class="block text-sm font-semibold text-gray-900 dark:text-white mb-1" :for="field.name">
            {{ props.field.label }}
        </label>

        <slot :validate="validate" />

        <p v-if="!field.hidden && props.error" class="text-sm text-red-300 mt-1">
            {{ props.error }}
        </p>
    </div>
</template>
