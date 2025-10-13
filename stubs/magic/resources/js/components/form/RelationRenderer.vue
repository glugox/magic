<script setup lang="ts">
import {computed} from "vue";
import {DbId, Entity, FormRelationProps, ResourceFormProps} from "@/types/support";

import ExpandableForm from "@/components/form/ExpandableForm.vue";
import HeadingSmall from "@/components/HeadingSmall.vue";
import ResourceCard from "@/components/resource/ResourceCard.vue";

const props = defineProps<FormRelationProps>()

/**
 * Get related entity from relation
 * To prevent circular imports, relatedEntity is a function returning the entity
 */
const entity = computed<Entity>(() => {
    const result = typeof props.relation.relatedEntity === 'function'
        ? props.relation.relatedEntity()
        : props.relation.relatedEntity;
    if (!result) {
        throw new Error('Related entity is not set');
    }
    return result;
});

const parentEntity = computed(() =>
   props.entity
)

</script>

<template>
    <div class="flex justify-between items-center border-b pb-2">
        <HeadingSmall
            :title="entity.singularName"
            :description="`Manage ${entity.singularName} details associated with this ${parentEntity.singularName}.`"
        />
    </div>

    <ExpandableForm
        :entity="entity"
        :parent-entity="parentEntity"
        :parent-id="parentId as DbId"
        :jsonMode="true"
        @created="$emit('created', $event)"
        @updated="$emit('updated', $event)"
        @deleted="$emit('deleted', $event)"
    >
        <template #field  >
            <ResourceCard :entity="entity" :id="parentId" />
        </template>
    </ExpandableForm>
</template>
