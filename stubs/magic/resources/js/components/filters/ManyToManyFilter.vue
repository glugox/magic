<template>
    <BaseFilter :label="filter.label" :initialValues="{ [filter.field]: [] }">
        <template #default="{ values }">
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        role="combobox"
                        class="mt-2 w-full justify-between text-foreground"
                    >
                        <!-- Pills or Placeholder -->
                        <span v-if="values[filter.field]?.length">
              {{ values[filter.field].join(", ") }}
            </span>
                        <span v-else class="text-muted-foreground">
              Select {{ filter.label }}
            </span>
                    </Button>
                </PopoverTrigger>

                <PopoverContent class="w-[240px] p-2 bg-background border border-border" stay-open>
                    <!-- Selected pills -->
                    <div
                        v-if="values[filter.field]?.length"
                        class="flex flex-wrap gap-1 mb-2"
                    >
                        <Badge
                            v-for="val in values[filter.field]"
                            :key="val"
                            variant="secondary"
                            class="cursor-pointer bg-emerald-100 text-emerald-800 border border-emerald-300"
                            @click="removeItem(val, values)"
                        >
                            {{ optionLabel(val) }} ✕
                        </Badge>
                    </div>

                    <!-- Options -->
                    <div class="mt-2 border-t pt-2 text-sm space-y-1">
                        <div
                            v-for="(label, val) in filter.options"
                            :key="val"
                            class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded transition-colors"
                            :data-selected="values[filter.field]?.includes(val)"
                            @click="toggleItem(val, values)"
                        >
                            {{ label }}
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
        </template>
    </BaseFilter>
</template>

<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import BaseFilter from "@/components/filters/BaseFilter.vue"
import type { FilterConfig } from "@/types/support"

const props = defineProps<{
    filter: FilterConfig
}>()

function toggleItem(val: string, values: Record<string, any>) {
    const arr = values[props.filter.field] || []
    if (arr.includes(val)) {
        values[props.filter.field] = arr.filter((v: string) => v !== val)
    } else {
        values[props.filter.field] = [...arr, val]
    }
}

function removeItem(val: string, values: Record<string, any>) {
    values[props.filter.field] = values[props.filter.field].filter((v: string) => v !== val)
}

function optionLabel(val: string) {
    return props.filter.options?.[val] ?? val
}
</script>
