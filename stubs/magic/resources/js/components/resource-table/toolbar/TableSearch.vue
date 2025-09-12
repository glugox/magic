<script setup lang="ts">
import { ref, watch } from "vue"
import { debounced } from "@/lib/app"

const props = defineProps<{
    placeholder?: string
    initialValue?: string
}>()

const emit = defineEmits<{
    (e: "update:search", value: string): void
}>()

const search = ref(props.initialValue ?? "")

// debounce
const sendDebounced = debounced(() => {
    emit("update:search", search.value)
}, 400)

watch(search, (newVal) => {
    if (!newVal || newVal.length >= 3) {
        sendDebounced()
    }
})
</script>

<template>
    <input
        v-model="search"
        type="search"
        :placeholder="placeholder || 'Search…'"
        class="border rounded px-2 py-1 w-52"
    />
</template>
