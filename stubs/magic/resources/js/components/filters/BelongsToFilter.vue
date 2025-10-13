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
          <span
              v-if="selectedOption"
              class="block max-w-[150px] truncate cursor-pointer"
              :title="selectedOption.name"
          >
            {{ selectedOption.name }}
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
                        :class="{
              'bg-muted font-medium': localValue === item.id
            }"
                        @click="selectItem(item.id)"
                    >
                        {{ item.name }}
                        <Check v-if="localValue === item.id" class="h-4 w-4 text-primary" />
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
import { useFilter } from "@/composables/useFilter"
import { Label, Button } from "@/components/ui"
import ResetButton from "@/components/ResetButton.vue"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Entity, FilterProps, TableFilterEmits } from "@/types/support"
import { useBelongsToOptions } from "@/composables/useBelongsToOptions"
import { Check } from "lucide-vue-next"

const props = defineProps<FilterProps>()
const { filter } = props
const emit = defineEmits<TableFilterEmits>()

const { label, entityRef } = filter
const { localValue, isDirty, reset } = useFilter(
    toRef(props, "filterValue"),
    (val) => emit("change", val),
    { defaultValue: null }
)

// Entity + relation metadata
const entity: Entity =
    typeof entityRef === "function" ? entityRef() : (entityRef as unknown as Entity)
const relationMetadata = entity.relations.find(
    (r) => r.relatedEntityName === filter.relatedEntityName
)!
const relatedEntityName = relationMetadata.relatedEntityName!

// Load options
const { options, isLoading, searchOptions, loadMore, hasMore } = useBelongsToOptions({
    relationMetadata: {
        apiPath: relationMetadata.apiPath || entity.name.toLowerCase(),
        relatedEntityName
    },
    autoRefreshOnCreate: false
})

const optionsListRef = ref<HTMLElement | null>(null)
const search = ref("")

// Infinite scroll
function handleScroll(e: Event) {
    const el = e.target as HTMLElement
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 30) {
        if (hasMore.value && !isLoading.value) {
            loadMore()
        }
    }
}

// Watch for search
watch(search, (term) => {
    if (term.length >= 2 || term.length === 0) {
        searchOptions(term)
    }
})

const filteredOptions = computed(() => {
    if (!search.value) return options.value
    return options.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.toLowerCase())
    )
})

// Select one item only
function selectItem(id: string) {
    localValue.value = id === localValue.value ? null : id
}

// Current selected option
const selectedOption = computed(() =>
    options.value.find((o) => o.id === localValue.value)
)
</script>
