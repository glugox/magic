<script setup lang="ts">

import {Button} from "@/components/ui/button";
import {ref, watch} from "vue";
import {Entity, Controller, DbId} from "@/types/support";
import {debounced} from "@/lib/app";
import ToolBarActions from "@/components/resource-table/ToolBarActions.vue";

// props
const { entity, controller, parentId } = defineProps<{
    entity: Entity
    controller: Controller
    parentId?: DbId
}>()

// state
const search = ref("")

// Emits
const emit = defineEmits<{
    (e: 'update:search', value: string): void
    (e: "bulk-action", action: "edit" | "delete" | "archive"): void
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
            :href="controller.create(parentId).url"
            as="a"
            class="ml-auto"
        >
            New {{ entity.singularName }}
        </Button>
        <ToolBarActions @action="action => emit('bulk-action', action)" />
    </div>
</template>

<style scoped>

</style>
