<template>
    <BaseFilter :label="label" :initialValues="{ min: undefined, max: undefined }">
        <template #default="{ values }">
            <div class="flex gap-2">
                <Input
                    v-model="values.min"
                    type="number"
                    placeholder="Min"
                    class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
                <Input
                    v-model="values.max"
                    type="number"
                    placeholder="Max"
                    class="flex-1 bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
            </div>
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Input } from "@/components/ui/input"
import type { FilterBaseProps } from "@/types/support"
import BaseFilter from "@/components/filters/BaseFilter.vue";

const props = defineProps<FilterBaseProps>()
</script>
