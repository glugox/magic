<script setup lang="ts">
import { ref, provide } from 'vue';
import ResourceForm from '@/components/ResourceForm.vue';
import DialogManager from '@/components/DialogManager.vue';
import {Entity, DbId, Relation, ResourceFormProps} from '@/types/support';

// Props
const { entity, item, parentEntity, parentId, inertiaPage } = defineProps<ResourceFormProps>();

// DialogManager ref
const dialogManager = ref<InstanceType<typeof DialogManager> | null>(null);

// Provide globally to children ResourceForm
provide('dialogManager', dialogManager);

// Handle open-related event from ResourceForm
function handleOpenRelated(relation: Relation) {

    console.log("Handle open related CCC", relation);
    if (!dialogManager.value) return;

    /** @type {Entity | null} */
    const relatedEntity: Entity | null = relation.relatedEntity ? relation.relatedEntity() : null;
    if (!relatedEntity) {
        console.error("Relation has no entity defined", relation);
        return;
    }

    const parentItemId = item?.id;

    dialogManager.value.openDialog({
        entity: relatedEntity,
        parentEntity: entity,
        parentId: parentItemId,
        title: relatedEntity.singularName,
        parentInertiaPage: inertiaPage,
        onSuccess(record, action) {
            console.log("Dialog Instance on Success :", action, record);
        },
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
            :inertia-page="inertiaPage"
            @open-related="handleOpenRelated"
        />

        <!-- Dialog manager for nested forms -->
        <DialogManager ref="dialogManager" />
    </div>
</template>
