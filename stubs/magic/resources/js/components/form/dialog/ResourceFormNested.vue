<script setup lang="ts">
import { ref, provide } from 'vue';
import ResourceForm from '@/components/ResourceForm.vue';
import DialogManager from '@/components/DialogManager.vue';
import type { Entity, DbId, Relation } from '@/types/support';

// Props
const { entity, item, parentEntity, parentId } = defineProps<{
    entity: Entity;
    item?: Record<string, any>;
    parentEntity?: Entity;
    parentId?: DbId;
}>();

// DialogManager ref
const dialogManager = ref<InstanceType<typeof DialogManager> | null>(null);

// Provide globally to children ResourceForm
provide('dialogManager', dialogManager);

// Handle open-related event from ResourceForm
function handleOpenRelated(relation: Relation) {

    console.log("Handle open related CCC", relation);
    if (!dialogManager.value) return;

    /** @type {Entity | null} */
    const relatedEntity = relation.relatedEntity();
    if (!relatedEntity) {
        console.error("Relation has no entity defined", relation);
        return;
    }

    // Update Entity with relation info
    relatedEntity.controller = relation.controller;

    console.log("Related entity:");
    console.log(relatedEntity);

    const parentItemId = item?.id;

    dialogManager.value.openDialog({
        entity: relatedEntity,
        parentEntity: entity,
        parentId: parentItemId,
        title: relatedEntity.singularName,
    });
}
</script>

<template>
    <div>
        <!-- Main ResourceForm (not in a dialog) -->
        <ResourceForm
            :entity="entity"
            :item="item"
            :parent-entity="parentEntity"
            :parent-id="parentId"
            @open-related="handleOpenRelated"
        />

        <!-- Dialog manager for nested forms -->
        <DialogManager ref="dialogManager" />
    </div>
</template>
