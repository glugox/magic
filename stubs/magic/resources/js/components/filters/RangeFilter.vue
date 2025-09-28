<template>
    <BaseFilter :label="label">
        <template #default="{ values }">
            <div class="flex gap-2">
                <Input
                    v-model="values.min"
                    name="range-min"
                    type="number"
                    placeholder="Min"
                    class="w-24 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none bg-background border border-input text-foreground placeholder:text-muted-foreground"
                />
                <Input
                    v-model="values.max"
                    name="range-max"
                    type="number"
                    placeholder="Max"
                    class="w-24 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none bg-background border border-input text-foreground placeholder:text-muted-foreground"
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
