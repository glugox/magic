<template>
    <div class="filter relative">
        <ResetButton v-if="isDirty" @click="reset" class="absolute top-0 right-0 -mt-1 -mr-1"/>

        <Label>{{ label }}</Label>

        <Popover>
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    class="mt-2 w-full justify-between text-foreground"
                >
                    <!-- Pills or Placeholder -->
                    <span v-if="localValue?.length">{{ localValue.map(optionLabel).join(", ") }}</span>
                    <span v-else class="text-muted-foreground">Select {{ label }}</span>
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
                        v-for="item in options"
                        :key="item.id"
                        class="cursor-pointer hover:bg-muted/50 px-2 py-1 rounded transition-colors"
                        :data-selected="localValue?.includes(item.id)"
                        @click="toggleItem(item.id)"
                    >
                        {{ item.name }}
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>

<script setup lang="ts">
import { toRef } from "vue"
import { useFilter } from "@/composables/useFilter"
import { Label, Button } from "@/components/ui"
import ResetButton from "@/components/ResetButton.vue"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Badge } from "@/components/ui/badge"
import {Entity, FilterProps, TableFilterEmits} from "@/types/support"
import { useBelongsToOptions } from "@/composables/useBelongsToOptions"

const props = defineProps<FilterProps>()
const { filter } = props
const { label, entityRef } = filter
const emit = defineEmits<TableFilterEmits>()

// Hook into useFilter for managing local filter value
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"),
    (val) => emit("change", val),
    { defaultValue: [] }
)

// Get entity (if entityRef is a function)
const entity: Entity = typeof entityRef === "function" ? entityRef() : entityRef as unknown as Entity

const relationMetadata = entity?.relations.find(r => r.relatedEntityName === filter.relatedEntityName)!
const relatedEntityName = relationMetadata.relatedEntityName!

console.log('entity', entity)
console.log('relationMetadata', relationMetadata)

// Use composable to fetch options dynamically
const { options } = useBelongsToOptions({
    relationMetadata: {
        apiPath: relationMetadata?.apiPath || entity.name.toLowerCase(),
        relatedEntityName: relatedEntityName
    },
    autoRefreshOnCreate: false
})

// Toggle items inside array filter
function toggleItem(val: string) {
    if (!Array.isArray(localValue.value)) localValue.value = []
    if (localValue.value.includes(val)) {
        localValue.value = localValue.value.filter(v => v !== val)
    } else {
        localValue.value = [...localValue.value, val]
    }
}

function removeItem(val: string) {
    if (Array.isArray(localValue.value)) {
        localValue.value = localValue.value.filter(v => v !== val)
    }
}

function optionLabel(val: string) {
    const found = options.value.find(o => o.id === val)
    return found ? found.name : val
}
</script>
