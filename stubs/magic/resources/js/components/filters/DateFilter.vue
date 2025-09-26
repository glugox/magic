<template>
    <BaseFilter
        :label="label"
        :initialValues="{ from: '', to: '' }"
    >
        <template #default="{ values }">
            <div class="flex gap-2">
                <Input
                    v-model="values.from"
                    type="date"
                    class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
                <Input
                    v-model="values.to"
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

const props = defineProps<FilterBaseProps>()
</script>
