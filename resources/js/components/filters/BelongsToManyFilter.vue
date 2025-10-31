<template>
    <div class="filter relative">
        <ResetButton
            v-if="isDirty"
            @click="reset"
            class="absolute top-0 right-0 -mt-1 -mr-1"
        />

        <Label>{{ label }}</Label>

        <Popover>
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    class="mt-2 w-full justify-between text-foreground"
                >
          <span v-if="localValue?.length" class="block max-w-[150px] truncate cursor-pointer"
                :title="selectedLabel">
            {{ selectedLabel }}
          </span>
                    <span v-else class="text-muted-foreground">
            Select {{ label }}
          </span>
                </Button>
            </PopoverTrigger>

            <PopoverContent class="w-[260px] p-2 bg-background border border-border" stay-open>
                <!-- Search -->
                <div class="mb-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search..."
                        class="w-full border border-border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

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

                <!-- Options list -->
                <div
                    ref="optionsListRef"
                    class="mt-2 border-t pt-2 text-sm space-y-1 max-h-[220px] overflow-auto"
                    @scroll="handleScroll"
                >
                    <div
                        v-for="item in filteredOptions"
                        :key="item.id"
                        class="flex justify-between cursor-pointer hover:bg-muted/50 px-2 py-1 rounded transition-colors"
                        :data-selected="localValue?.includes(item.id)"
                        :class="{
                            'bg-muted': localValue?.includes(item.id),
                            'font-medium': localValue?.includes(item.id)
                          }"
                        @click="toggleItem(item.id)"
                    >
                        {{ item.name }}
                        <span class="text-white" v-if="localValue?.includes(item.id)">
                            <Check class="h-4 w-4" />
                        </span>
                    </div>

                    <div v-if="isLoading" class="text-xs text-muted-foreground px-2 py-1">
                        Loading...
                    </div>

                    <div
                        v-if="!isLoading && !filteredOptions.length"
                        class="text-xs text-muted-foreground px-2 py-1"
                    >
                        No results found
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, toRef, watch } from "vue"
import { useFilter } from "@glugox/module/composables/useFilter"
import { Label, Button } from "@/components/ui"
import ResetButton from "@glugox/module/components/ResetButton.vue"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Badge } from "@/components/ui/badge"
import { Entity, FilterProps, TableFilterEmits } from "@glugox/module/types/support"
import { useBelongsToOptions } from "@glugox/module/composables/useBelongsToOptions"
import { Check } from 'lucide-vue-next';

const props = defineProps<FilterProps>()
const { filter } = props
const emit = defineEmits<TableFilterEmits>()

const { label, entityRef } = filter
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"),
    (val) => emit("change", val),
    { defaultValue: [] }
)

// Entity
const entity: Entity = typeof entityRef === "function" ? entityRef() : entityRef as unknown as Entity
const relationMetadata = entity.relations.find(r => r.relatedEntityName === filter.relatedEntityName)!
const relatedEntityName = relationMetadata.relatedEntityName!

// Belongs-to composable
const { options, isLoading, searchOptions, loadMore, hasMore } = useBelongsToOptions({
    relationMetadata: {
        apiPath: relationMetadata.apiPath || entity.name.toLowerCase(),
        relatedEntityName
    },
    autoRefreshOnCreate: false
})

// Selected label
const selectedLabel = computed(() => {
    if (!localValue.value || (Array.isArray(localValue.value) && localValue.value.length === 0)) {
        return `Select ${label}`
    }
    if (Array.isArray(localValue.value) && localValue.value.length === 1) {
        const found = options.value.find(o => o.id === localValue.value![0])
        return found ? found.name : localValue.value![0]
    }
    return `${localValue.value.length} selected`
})


const optionsListRef = ref<HTMLElement | null>(null)
const search = ref("")

// Infinite scroll detection
function handleScroll(e: Event) {
    const el = e.target as HTMLElement
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 30) {
        if (hasMore.value && !isLoading.value) {
            loadMore()
        }
    }
}

watch(search, (term) => {
    if (term.length >= 2 || term.length === 0) {
        searchOptions(term)
    }
})

const filteredOptions = computed(() => {
    if (!search.value) return options.value
    return options.value.filter(o =>
        o.name.toLowerCase().includes(search.value.toLowerCase())
    )
})

// Toggle selection
function toggleItem(val: string) {
    if (!Array.isArray(localValue.value)) localValue.value = []
    if (localValue.value.includes(val)) {
        localValue.value = localValue.value.filter(v => v !== val)
    } else {
        localValue.value = [...localValue.value, val]
    }
}

// Remove selection
function removeItem(val: string) {
    if (Array.isArray(localValue.value)) {
        localValue.value = localValue.value.filter(v => v !== val)
    }
}

// Label resolver
function optionLabel(val: string) {
    const found = options.value.find(o => o.id === val)
    return found ? found.name : val
}
</script>

