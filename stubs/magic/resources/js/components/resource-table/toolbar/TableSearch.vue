<script setup lang="ts">
import {onUnmounted, ref, watch} from "vue"
import { debounced } from "@/lib/app"
import {setFilterValue, subscribeToFilters} from "@/store/tableFiltersStore";
import InputField from "@/components/form/InputField.vue";

const {tableId, initialValue} = defineProps<{
    tableId: string
    placeholder?: string
    initialValue?: string
}>()

// To avoid loops when external filter changes
let lastExternalValue: string | null = null

const search = ref(initialValue ?? "")

// debounce
const sendDebounced = debounced(() => {
    setFilterValue(tableId, 'search', search.value)
}, 400)

watch(search, (newVal) => {

    // Skip if change came from external update
    if (newVal === lastExternalValue) {
        lastExternalValue = null
        return
    }

    if (!newVal || newVal.length >= 3) {
        sendDebounced()
    }
})

// Subscribe to external filter changes
// External change for search filter could be only from resetting filters
// So we update our local search value if it's different and make sure we don't
// trigger the watcher
const filtersListener = subscribeToFilters(tableId, (f) => {
    const externalValue = f.search as string || ""
    if (externalValue !== search.value) {
        lastExternalValue = externalValue
        search.value = f.search as string || ''
    }
})

// Cleanup on unmount
onUnmounted(() => filtersListener())

</script>

<template>
    <InputField
        v-model="search"
        type="search"
        :placeholder="placeholder || 'Search…'"
        class="w-52"
    />
</template>
