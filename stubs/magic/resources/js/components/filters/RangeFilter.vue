<template>
    <BaseFilter :label="label">
        <template #default="{ values }">
            <div class="flex gap-2">
                <InputField
                    v-model="values.min"
                    name="range-min"
                    type="number"
                    placeholder="Min"
                    class="w-24"
                />
                <InputField
                    v-model="values.max"
                    name="range-max"
                    type="number"
                    placeholder="Max"
                    class="w-24"
                />
            </div>
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Input } from "@/components/ui/input"
import type { FilterBaseProps } from "@/types/support"
import BaseFilter from "@/components/filters/BaseFilter.vue";
import InputField from "@/components/form/InputField.vue";

const props = defineProps<FilterBaseProps>()
</script>
