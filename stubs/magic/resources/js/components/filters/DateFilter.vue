<template>
    <BaseFilter
        :label="label"
    >
        <template #default="{ values }">
            <div class="flex gap-2">
                <InputField
                    v-model="values.min"
                    type="date"
                    class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
                <InputField
                    v-model="values.max"
                    type="date"
                    class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
            </div>
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Input } from "@/components/ui/input"
import BaseFilter from "@/components/filters/BaseFilter.vue"
import type { FilterBaseProps } from "@/types/support"
import InputField from "@/components/form/InputField.vue";

const props = defineProps<FilterBaseProps>()
</script>
