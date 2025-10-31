<script setup lang="ts">
import {computed} from "vue";
import {DbId, Entity, FormRelationProps, ResourceData} from "@glugox/module/types/support";

import ExpandableForm from "@glugox/module/components/form/ExpandableForm.vue";
import HeadingSmall from "@glugox/module/components/HeadingSmall.vue";
import ResourceCard from "@glugox/module/components/resource/ResourceCard.vue";

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

const foreignKey = computed(() =>
    props.relation.foreignKey
)

const relatedData = computed(() =>
        (props.item as ResourceData) ?? {
            [String(foreignKey.value)]: props.parentId
        } as ResourceData
)

const relatedId = computed<DbId | null>(() =>
    relatedData.value ? relatedData.value.id as DbId : null
)

</script>

<template>
    <div class="flex justify-between items-center border-b pb-2">
        <HeadingSmall
            :title="entity.singularName"
            :description="!parentId ? `You have to create ${parentEntity.singularName} in order to manage this record.` : `Manage ${entity.singularName} details associated with this ${parentEntity.singularName}.`"
        />
    </div>

    <ExpandableForm
        :entity="entity"
        :parent-entity="parentEntity"
        :id="relatedId"
        :item="relatedData"
        :allow-expand="!!parentId"
        :force-load="true"
        :jsonMode="true"
    >
        <template #field  >
            <ResourceCard :entity="entity" :id="relatedId" />
        </template>
    </ExpandableForm>
</template>
