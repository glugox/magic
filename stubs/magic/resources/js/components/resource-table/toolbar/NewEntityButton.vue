<script setup lang="ts">
import { Button } from "@/components/ui/button"
import type { Entity, Controller, DbId } from "@/types/support"

const props = defineProps<{
    entity: Entity
    controller: Controller
    parentId?: DbId
}>()
</script>

<template>
    <Button
        v-if="entity && controller && controller.create"
        variant="default"
        size="sm"
        :href="controller.create(parentId).url"
        as="a"
        class="ml-auto"
    >
        New {{ entity.singularName }}
    </Button>
</template>
