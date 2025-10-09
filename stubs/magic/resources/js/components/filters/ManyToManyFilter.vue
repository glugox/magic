<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" />

        <Label>{{ props.filter.label }}</Label>

        <Popover>
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    class="mt-2 w-full justify-between text-foreground"
                >
                    <!-- Pills or Placeholder -->
                    <span v-if="localValue?.length">
            {{ localValue.join(", ") }}
          </span>
                    <span v-else class="text-muted-foreground">
            Select {{ props.filter.label }}
          </span>
                </Button>
            </PopoverTrigger>

            <PopoverContent class="w-[240px] p-2 bg-background border border-border" stay-open>
                <!-- Selected pills -->
                <div v-if="localValue?.length" class="flex flex-wrap gap-1 mb-2">
                    <Badge
                        v-for="val in localValue"
                        :key="val"
                        variant="secondary"
                        class="cursor-pointer bg-emerald-100 text-emerald-800 border border-emerald-300"
                        @click="removeItem(val)"
                    >
                        {{ optionLabel(val) }} ✕
                    </Badge>
                </div>

                <!-- Options -->
                <div class="mt-2 border-t pt-2 text-sm space-y-1">
                    <div
                        v-for="(label, val) in props.filter.options"
                        :key="val"
                        class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded transition-colors"
                        :data-selected="localValue?.includes(val)"
                        @click="toggleItem(val)"
                    >
                        {{ label }}
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>

<script setup lang="ts">
import { toRef } from "vue";
import { useFilter } from "@/composables/useFilter";
import { Label, Button } from "@/components/ui";
import ResetButton from "@/components/ResetButton.vue";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Badge } from "@/components/ui/badge";
import type { FilterConfig, TableFilterEmits } from "@/types/support";

const props = defineProps<{ filter: FilterConfig }>();
const emit = defineEmits<TableFilterEmits>();

// Hook into composable
const { localValue, isDirty, reset } = useFilter(
    toRef(props.filter, "filterValue"),
    (val) => emit("change", val),
    { defaultValue: [] } // default is empty array
);

// Toggle items inside array filter
function toggleItem(val: string) {
    if (!Array.isArray(localValue.value)) {
        localValue.value = [];
    }
    if (localValue.value.includes(val)) {
        localValue.value = localValue.value.filter((v: string) => v !== val);
    } else {
        localValue.value = [...localValue.value, val];
    }
}

function removeItem(val: string) {
    if (Array.isArray(localValue.value)) {
        localValue.value = localValue.value.filter((v: string) => v !== val);
    }
}

function optionLabel(val: string) {
    return props.filter.options?.[val] ?? val;
}
</script>
