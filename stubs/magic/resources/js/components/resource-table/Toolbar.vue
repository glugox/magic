<script setup lang="ts">

import {Button} from "@/components/ui/button";
import {ref, watch} from "vue";
import {Entity, Controller} from "@/types/support";
import {debounced} from "@/lib/app";

// props
const { entity, controller } = defineProps<{
    entity: Entity
    controller: Controller
}>()

// state
const search = ref("")

// Emits
const emit = defineEmits<{
    (e: 'update:search', value: string): void
}>()

// Track search input with debounce and emit event
const sendDebounced = debounced(() => {
    emit('update:search', search.value)
}, 400)
watch(search, () => {
    sendDebounced()
})
</script>

<template>
    <div class="flex gap-2 items-center">
        <input
            v-model="search"
            type="search"
            placeholder="Search…"
            class="border rounded px-2 py-1 w-64"
        />
        <Button
            v-if="entity && controller && controller.create"
            :href="controller.create().url"
            as="a"
            class="ml-auto"
        >
            New {{ entity.singularName }}
        </Button>
    </div>
</template>

<style scoped>

</style>
